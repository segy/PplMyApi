<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Salamek\PplMyApi\Model;


use Salamek\PplMyApi\Enum\Depo;
use Salamek\PplMyApi\Enum\Product;
use Salamek\PplMyApi\Exception\WrongDataException;
use Salamek\PplMyApi\Tools;
use Salamek\PplMyApi\Validators\MaxLengthValidator;

class Package implements IPackage
{
    /** @var string */
    protected $packageNumber;

    /** @var integer */
    protected $packageProductType;

    /** @var float */
    protected $weight;

    /** @var string */
    protected $note;

    /** @var string */
    protected $depoCode;

    /** @var ISender */
    protected $sender;

    /** @var IRecipient */
    protected $recipient;

    /** @var null|ISpecialDelivery */
    protected $specialDelivery = null;

    /** @var null|IPaymentInfo */
    protected $paymentInfo = null;

    /** @var null|IExternalNumber[] */
    protected $externalNumbers = [];

    /** @var IPackageService[] */
    protected $packageServices = [];

    /** @var IFlag[] */
    protected $flags = [];

    /** @var null|IPalletInfo */
    protected $palletInfo = null;

    /** @var null|IWeightedPackageInfo */
    protected $weightedPackageInfo = null;

    /** @var int */
    protected $packageCount = 1;

    /** @var int */
    protected $packagePosition = 1;

    /**
     * Package constructor.
     * @param string $packageNumber Package number (40990019352)
     * @param int $packageProductType Product type
     * @param float $weight weight
     * @param string $note note
     * @param string $depoCode code of depo, see Enum\Depo.php
     * @param ISender $sender
     * @param IRecipient $recipient
     * @param null|ISpecialDelivery $specialDelivery
     * @param null|IPaymentInfo $paymentInfo
     * @param IExternalNumber[] $externalNumbers
     * @param IPackageService[] $packageServices
     * @param IFlag[] $flags
     * @param null|IPalletInfo $palletInfo
     * @param null|IWeightedPackageInfo $weightedPackageInfo
     * @param integer $packageCount
     * @param integer $packagePosition
     * @param bool $forceOwnPackageNumber
     * @throws WrongDataException
     */
    public function __construct(
        $packageNumber,
        $packageProductType,
        $weight,
        $note,
        $depoCode,
        $recipient,
        $sender = null,
        ISpecialDelivery $specialDelivery = null,
        IPaymentInfo $paymentInfo = null,
        array $externalNumbers = [],
        array $packageServices = [],
        array $flags = [],
        IPalletInfo $palletInfo = null,
        IWeightedPackageInfo $weightedPackageInfo = null,
        $packageCount = 1,
        $packagePosition = 1,
        $forceOwnPackageNumber = false
    ) {
        if ($this->isCashOnDelivery($packageProductType) && is_null($paymentInfo)) {
            throw new WrongDataException('$paymentInfo must be set if product type is CoD');
        }

        //!FIXME
        if ($recipient instanceof ISender)
        {
            user_error('Passing ISender as 6th parameter  of Package::__constructor is deprecated! ISender is now on 7th place of Package::__constructor, this compatibility layer will be removed in future.', E_USER_DEPRECATED);
            if ($recipient instanceof EmptySender)
            {
                $this->setSender(null);
                user_error('Using EmptySender is deprecated, please pass null instead, EmptySender will be removed in future.', E_USER_DEPRECATED);
            }
            else
            {
                $this->setSender($recipient);
            }
        }
        else if ($recipient instanceof IRecipient)
        {
            $this->setRecipient($recipient);
        }

        //!FIXME
        if ($sender instanceof IRecipient)
        {
            user_error('Passing IRecipient as 7th parameter of Package::__constructor is deprecated! IRecipient is now on 6th place of Package::__constructor, this compatibility layer will be removed in future.', E_USER_DEPRECATED);
            $this->setRecipient($sender);
        }
        else if ($sender instanceof ISender)
        {
            if ($sender instanceof EmptySender)
            {
                $this->setSender(null);
                user_error('Using EmptySender is deprecated, please pass null instead, EmptySender will be removed in future.', E_USER_DEPRECATED);
            }
            else
            {
                $this->setSender($sender);
            }
        }

        $packageNumberInfo = new PackageNumberInfo(null, $packageProductType, $depoCode, $this->isCashOnDelivery($packageProductType));

        $this->setPackageNumber($packageNumber);
        $this->setPackageProductType($packageProductType);
        $this->setWeight($weight);
        $this->setNote($note);
        $this->setDepoCode($depoCode);
        $this->setSpecialDelivery($specialDelivery);
        $this->setPaymentInfo($paymentInfo);
        $this->setExternalNumbers($externalNumbers);
        $this->setPackageServices($packageServices);
        $this->setFlags($flags);
        $this->setPalletInfo($palletInfo);
        $this->setWeightedPackageInfo($weightedPackageInfo);
        $this->setPackageCount($packageCount);
        $this->setPackagePosition($packagePosition);

        if (in_array($flags, Product::$deliverySaturday) && is_null($palletInfo)) {
            throw new WrongDataException('Package requires Salamek\PplMyApi\Enum\Flag::SATURDAY_DELIVERY to be true or false');
        }
    }

    /**
     * @param null|string $note
     * @throws WrongDataException
     */
    public function setNote($note = null)
    {
        MaxLengthValidator::validate($note, 300);

        $this->note = $note;
    }

    /**
     * @param $packageProductType
     * @throws WrongDataException
     */
    public function setPackageProductType($packageProductType)
    {
        if (!in_array($packageProductType, Product::$list)) {
            throw new WrongDataException(sprintf('$packageProductType has wrong value, only %s are allowed', implode(', ', Product::$list)));
        }
        $this->packageProductType = $packageProductType;
    }

    /**
     * @param string $packageNumber
     */
    public function setPackageNumber($packageNumber)
    {
        $this->packageNumber = $packageNumber;
    }

    /**
     * @param float $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * @param string $depoCode
     * @throws WrongDataException
     */
    public function setDepoCode($depoCode)
    {
        if (!in_array($depoCode, Depo::$list)) {
            throw new WrongDataException(sprintf('$depoCode has wrong value, only %s are allowed', implode(', ', Depo::$list)));
        }
        $this->depoCode = $depoCode;
    }

    /**
     * @param ISender $sender
     */
    public function setSender(ISender $sender = null)
    {
        $this->sender = $sender;
    }

    /**
     * @param IRecipient $recipient
     */
    public function setRecipient(IRecipient $recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * @param null|ISpecialDelivery $specialDelivery
     */
    public function setSpecialDelivery(ISpecialDelivery $specialDelivery = null)
    {
        $this->specialDelivery = $specialDelivery;
    }

    /**
     * @param null|IPaymentInfo $paymentInfo
     */
    public function setPaymentInfo(IPaymentInfo $paymentInfo = null)
    {
        $this->paymentInfo = $paymentInfo;
    }

    /**
     * @param IExternalNumber[] $externalNumbers
     */
    public function setExternalNumbers(array $externalNumbers)
    {
        $this->externalNumbers = $externalNumbers;
    }

    /**
     * @param IPackageService[] $packageServices
     */
    public function setPackageServices(array $packageServices)
    {
        $this->packageServices = $packageServices;
    }

    /**
     * @param IFlag[] $flags
     */
    public function setFlags(array $flags)
    {
        $this->flags = $flags;
    }

    /**
     * @param null|IPalletInfo $palletInfo
     */
    public function setPalletInfo(IPalletInfo $palletInfo = null)
    {
        $this->palletInfo = $palletInfo;
    }

    /**
     * @param null|IWeightedPackageInfo $weightedPackageInfo
     */
    public function setWeightedPackageInfo(IWeightedPackageInfo $weightedPackageInfo = null)
    {
        $this->weightedPackageInfo = $weightedPackageInfo;
    }

    /**
     * @param int $packageCount
     */
    public function setPackageCount($packageCount)
    {
        $this->packageCount = $packageCount;
    }

    /**
     * @param int $packagePosition
     */
    public function setPackagePosition($packagePosition)
    {
        $this->packagePosition = $packagePosition;
    }

    /**
     * @return string
     */
    public function getPackageNumber()
    {
        return $this->packageNumber;
    }

    /**
     * @return string
     */
    public function getPackageNumberChecksum()
    {
        $checksum = null;
        $odd = 0;
        $even = 0;
        for ($i = 0; $i < strlen($this->packageNumber); $i++) {
            $n = substr($this->packageNumber, $i, 1);
            if (!($i % 2)) {
                $odd += $n;
            } else {
                $even += $n;
            }
        }
        $odd *= 3;
        $odd += $even;
        $checksum = 10 - substr($odd, -1);
        if ($checksum == 10) $checksum = 0;
        return $checksum;

    }

    /**
     * @return int
     */
    public function getPackageProductType()
    {
        return $this->packageProductType;
    }

    /**
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @return string
     */
    public function getDepoCode()
    {
        return $this->depoCode;
    }

    /**
     * @return ISender
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @return IRecipient
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @return null|ISpecialDelivery
     */
    public function getSpecialDelivery()
    {
        return $this->specialDelivery;
    }

    /**
     * @return null|IPaymentInfo
     */
    public function getPaymentInfo()
    {
        return $this->paymentInfo;
    }

    /**
     * @return IExternalNumber[]
     */
    public function getExternalNumbers()
    {
        return $this->externalNumbers;
    }

    /**
     * @return IPackageService[]
     */
    public function getPackageServices()
    {
        return $this->packageServices;
    }

    /**
     * @return IFlag[]
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @return IPalletInfo
     */
    public function getPalletInfo()
    {
        return $this->palletInfo;
    }

    /**
     * @return null|IWeightedPackageInfo
     */
    public function getWeightedPackageInfo()
    {
        return $this->weightedPackageInfo;
    }


    /**
     * @return int
     */
    public function getPackageCount()
    {
        return $this->packageCount;
    }

    /**
     * @return int
     */
    public function getPackagePosition()
    {
        return $this->packagePosition;
    }

    /**
     * @param int
     * @return bool
     */
    public function isCashOnDelivery($packageProductType = null)
    {
        if (is_null($packageProductType)) {
            $packageProductType = $this->getPackageProductType();
        }

        return in_array($packageProductType, Product::$cashOnDelivery);
    }
}
