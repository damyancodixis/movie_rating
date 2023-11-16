<?php

namespace App\EventListener;

use App\Entity\Review;
use Symfony\Bundle\SecurityBundle\Security;

class ReviewListener
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function prePersist(Review $review)
    {
        $review->setCreatedBy($this->security->getUser());
        $review->setCreatedAt(new \DateTime());
    }
}
