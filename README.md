# Php Enum doctrine

* Allows to add you custom php enums as doctrine type.
* Supports generation of correct alter SQL when updating enum values.

Extension for [php-enum](https://github.com/gusarov112/php-enum) (myclabs fork)

## Installation

Than require package
```bash
composer require gusarov112/php-enum-doctrine
```

## Symfony configuration
```
  Gusarov112\PhpEnumDoctrine\EventListener\EnumUpdaterSubscriber:
    tags:
      - { name: doctrine.event_subscriber }
```

## Example

### Create enum
```php
class PetEnum extends \Gusarov112\Enum\Enum
{
    const CAT = 'CAT';
    const DOG = 'DOG';
}
```
### Create corresponding enum type by extending abstract type
```php
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
```
### Use your column type in doctrine entity
```php
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
```
### Do not forger to register your type
```php
\Doctrine\DBAL\Types\Type::addType('pet_type', PetType::class);
```
### Add event subscriber if you have migrations bundle and want to autogenerate enum list alter SQL's
```php
$eventManager = new \Doctrine\Common\EventManager();
$eventManager->addEventSubscriber(new \Gusarov112\PhpEnumDoctrine\EventListener\EnumUpdaterSubscriber());

#!/bin/bash ./vendor/bin/doctrine migrations:diff
```
