<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class IsReviewComplete extends Constraint {
    public string $errorMessage = "Reviews must have title and content";

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}