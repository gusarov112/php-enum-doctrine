<?php
declare(strict_types=1);

class PetEnum extends \Gusarov112\Enum\Enum
{
    const CAT = 'CAT';
    const DOG = 'DOG';
}

class PetType extends \Gusarov112\PhpEnumDoctrine\DBAL\EnumType
{
    public function getEnumClassName(): string
    {
        return PetEnum::class;
    }

    public function getName()
    {
        return 'pet_type';
    }
}

use Doctrine\ORM\Mapping as ORM;

class PetEntity {
    /**
     * @var PetEnum
     * @ORM\Column(type="pet_type")
     */
    private $type;

    public function getType(): PetEnum
    {
        return $this->type;
    }

    public function setType(PetEnum $type): self
    {
        $this->type = $type;

        return $this;
    }
}

\Doctrine\DBAL\Types\Type::addType('pet_type', PetType::class);

$eventManager = new \Doctrine\Common\EventManager();
$eventManager->addEventSubscriber(new \Gusarov112\PhpEnumDoctrine\EventListener\EnumUpdaterSubscriber());
#!/bin/bash ./vendor/bin/doctrine migrations:diff


