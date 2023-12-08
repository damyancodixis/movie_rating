<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationErrorHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
    }

    public function getErrors($entity): array
    {
        if (!$this->isEntity($entity)) {
            throw new InvalidTypeException('Non-entity type was passed to validation service');
        }

        $errors = $this->validator->validate($entity);

        $messages = [];
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
        }

        return $messages;
    }

    function isEntity(string|object $class): bool
    {
        if (is_object($class)) {
            $class = ($class instanceof Proxy)
                ? get_parent_class($class)
                : get_class($class);
        }

        return !$this->entityManager->getMetadataFactory()->isTransient($class);
    }
}
