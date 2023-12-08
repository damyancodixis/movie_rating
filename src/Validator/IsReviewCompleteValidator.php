<?php

namespace App\Validator;

use App\Entity\Review;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class IsReviewCompleteValidator extends ConstraintValidator
{
    public function validate($review, Constraint $constraint): void
    {
        if (!$review instanceof Review) {
            throw new UnexpectedValueException($review, Review::class);
        }

        if (!$constraint instanceof IsReviewComplete) {
            throw new UnexpectedTypeException($constraint, IsReviewComplete::class);
        }

        $titleSet = $review->getTitle() !== null && strlen($review->getTitle()) > 0;
        $contentSet = $review->getContent() !== null && strlen($review->getContent()) > 0;
        $bothSet = $titleSet && $contentSet;
        $bothNull = $review->getTitle() === null && $review->getContent() === null;

        if (!($bothNull || $bothSet)) {
            $this->context
                ->buildViolation($constraint->errorMessage)
                ->atPath('review')
                ->addViolation();
        }
    }
}