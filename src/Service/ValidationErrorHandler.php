<?php

namespace App\Service;

use Symfony\Component\Validator\ConstraintViolationList;

class ValidationErrorHandler
{
    public function getValidationErrors(ConstraintViolationList $errors): array
    {
        $messages = [];
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
        }

        return $messages;
    }
}
