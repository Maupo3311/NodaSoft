<?php

namespace NW\WebService\References\Operations\Notification;

use Exception;
use NW\WebService\References\Operations\Notification\Constraints\DateConstraint;
use NW\WebService\References\Operations\Notification\Constraints\IntConstraint;
use NW\WebService\References\Operations\Notification\Constraints\NotNullConstraint;
use NW\WebService\References\Operations\Notification\Constraints\OneOfConstraint;
use NW\WebService\References\Operations\Notification\Constraints\StringConstraint;
use NW\WebService\References\Operations\Notification\Constraints\TemplateConstraint;
use NW\WebService\References\Operations\Notification\Enum\DoOperationTypeEnum;
use NW\WebService\References\Operations\Notification\Enum\HttpCodeEnum;
use NW\WebService\References\Operations\Notification\Exception\ViolationsException;
use NW\WebService\References\Operations\Notification\Validator\Validator;

class ReturnOperation extends ReferencesOperation
{
    /**
     * @throws Exception
     */
    public function doOperation(): array
    {
        $violations = $this->validateRequest();
        if (!empty($violations)) {
            throw new ViolationsException($violations);
        }
        $data = $this->getDataFromRequest();

        $resellerId = $data['resellerId'];
        $client = Contractor::getById($data['clientId']);
        if (null === $client || $client->type !== Contractor::TYPE_CUSTOMER || $client->seller->id !== $resellerId) {
            throw new Exception('Client not found!', HttpCodeEnum::BAD_REQUEST);
        }

        $templateData = $this->getTemplateData($data, $client);
        $emailFrom = getResellerEmailFrom($resellerId);
        $result = [
            'notificationEmployeeByEmail' => false,
            'notificationClientByEmail' => false,
            'notificationClientBySms' => [
                'isSent' => false,
                'message' => null,
            ],
        ];
        if (null !== $emailFrom) {
            $result['notificationEmployeeByEmail'] = $this->sendEmployeeEmails(
                $emailFrom,
                $templateData,
                $resellerId,
                $data['notificationType'] === DoOperationTypeEnum::NEW
            );
        }
        $differenceTo = $data['differences']['to'] ?? null;
        // Шлём клиентское уведомление, только если произошла смена статуса
        if ($data['notificationType'] === DoOperationTypeEnum::CHANGE && null !== $differenceTo) {
            if (null !== $emailFrom) {
                $result['notificationEmployeeByEmail'] = $this->sendClientEmail(
                    $client,
                    $emailFrom,
                    $templateData,
                    $resellerId,
                    $differenceTo
                );
            }
            $result['notificationClientBySms'] = $this->sendClientNotification($client, $templateData, $resellerId, $differenceTo);
        }

        return $result;
    }

    private function sendClientNotification(Contractor $client, array $templateData, int $resellerId, int $differencesTo): array
    {
        $notificationData = [
            'isSent' => false,
            'message' => null,
        ];
        if (!empty($client->mobile)) {
            $response = NotificationManager::send(
                $resellerId,
                $client->id,
                NotificationEvents::CHANGE_RETURN_STATUS,
                $differencesTo,
                $templateData,
                $error // Ссылка
            );
            $notificationData['isSent'] = null !== $response;
            $notificationData['message'] = $error ?: null;
        }
        return $notificationData;
    }

    private function sendClientEmail(
        Contractor $client,
        string $emailFrom,
        array $templateData,
        int $resellerId,
        int $differencesTo
    ): bool {
        if (!empty($emailFrom) && !empty($client->email)) {
            $message = [
                'emailFrom' => $emailFrom,
                'emailTo'   => $client->email,
                'subject'   => __('complaintClientEmailSubject', $templateData, $resellerId),
                'message'   => __('complaintClientEmailBody', $templateData, $resellerId),
            ];
            MessagesClient::sendMessage(
                [$message],
                $resellerId,
                $client->id,
                NotificationEvents::CHANGE_RETURN_STATUS,
                $differencesTo
            );
            return true;
        }
        return false;
    }

    private function sendEmployeeEmails(string $emailFrom, array $templateData, int $resellerId, bool $isNew): bool
    {
        $emails = getEmailsByPermit($resellerId, 'tsGoodsReturn');
        $subject = __('complaintEmployeeEmailSubject', $templateData, $resellerId);
        $message = __('complaintEmployeeEmailBody', $templateData, $resellerId);
        $messages = [];
        foreach ($emails as $email) {
            $messages[] = [
                'emailFrom' => $emailFrom,
                'emailTo'   => $email,
                'subject'   => $subject,
                'message'   => $message,
            ];
        }
        if (!empty($messages)) {
            MessagesClient::sendMessage(
                $messages,
                $resellerId,
                $isNew ? NotificationEvents::NEW_RETURN_STATUS : NotificationEvents::CHANGE_RETURN_STATUS
            );
            return true;
        }
        return false;
    }

    /**
     * @throws Exception
     */
    private function getTemplateData(array $data, Contractor $client): array
    {
        $reseller = Seller::getById($data['resellerId']);
        if (null === $reseller) {
            throw new Exception('Seller not found!', HttpCodeEnum::BAD_REQUEST);
        }

        $creator = Employee::getById($data['creatorId']);
        if (null === $creator) {
            throw new Exception('Creator not found!', HttpCodeEnum::BAD_REQUEST);
        }

        $expert = Employee::getById($data['expertId']);
        if (null === $expert) {
            throw new Exception('Expert not found!', HttpCodeEnum::BAD_REQUEST);
        }

        return [
            'COMPLAINT_ID' => $data['complaintId'],
            'COMPLAINT_NUMBER' => $data['complaintNumber'],
            'CREATOR_ID' => $creator->getId(),
            'CREATOR_NAME' => $creator->getFullName(),
            'EXPERT_ID' => $expert->getId(),
            'EXPERT_NAME' => $expert->getFullName(),
            'CLIENT_ID' => $client->getId(),
            'CLIENT_NAME' => $client->getFullName(),
            'CONSUMPTION_ID' => $data['consumptionId'],
            'CONSUMPTION_NUMBER' => $data['consumptionNumber'],
            'AGREEMENT_NUMBER' => $data['agreementNumber'],
            'DATE' => $data['date'],
            'DIFFERENCES' => $this->getDifferences($data['notificationType'], $data['resellerId'], $data['differences']),
        ];
    }

    private function getDifferences(int $notificationType, int $resellerId, ?array $differences): string
    {
        if ($notificationType === DoOperationTypeEnum::NEW) {
            return __('NewPositionAdded', null, $resellerId);
        } elseif ($notificationType === DoOperationTypeEnum::CHANGE && !empty($differences)) {
            return __('PositionStatusHasChanged', [
                'FROM' => Status::getName($differences['from']),
                'TO' => Status::getName($differences['to']),
            ], $resellerId);
        }
        return '';
    }

    /**
     *  @return array{
     *      resellerId: int,
     *      notificationType: int,
     *      clientId: int,
     *      creatorId: int,
     *      expertId: int,
     *      differences: array{from: int, to: int},
     *      complaintId: int,
     *      complaintNumber: string,
     *      consumptionId: int,
     *      consumptionNumber: string,
     *      agreementNumber: string,
     *      date: string
     *  }
     */
    private function getDataFromRequest(): array
    {
        return $_REQUEST['data'];
    }

    private function validateRequest(): array
    {
        $validator = new Validator();
        $templateConstraint = new TemplateConstraint([
            'data' => new TemplateConstraint([
                'resellerId' => [
                    NotNullConstraint::init(),
                    IntConstraint::init(),
                ],
                'notificationType' => [
                    NotNullConstraint::init(),
                    new OneOfConstraint(DoOperationTypeEnum::getValues()),
                ],
                'clientId' => [
                    NotNullConstraint::init(),
                    IntConstraint::init(),
                ],
                'creatorId' => [
                    NotNullConstraint::init(),
                    IntConstraint::init(),
                ],
                'expertId' => [
                    NotNullConstraint::init(),
                    IntConstraint::init(),
                ],
                'differences' => new TemplateConstraint([
                    'from' => IntConstraint::init(),
                    'to' => IntConstraint::init(),
                ]),
                'complaintId' => [
                    NotNullConstraint::init(),
                    IntConstraint::init(),
                ],
                'complaintNumber' => [
                    NotNullConstraint::init(),
                    StringConstraint::init(),
                ],
                'consumptionId' => [
                    NotNullConstraint::init(),
                    IntConstraint::init(),
                ],
                'consumptionNumber' => [
                    NotNullConstraint::init(),
                    StringConstraint::init(),
                ],
                'agreementNumber' => [
                    NotNullConstraint::init(),
                    StringConstraint::init(),
                ],
                'date' => [
                    NotNullConstraint::init(),
                    new DateConstraint('d.m.Y H:i'),
                ]
            ])
        ]);
        return $validator->validate($templateConstraint, $_REQUEST);
    }
}
