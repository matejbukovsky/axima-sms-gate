<?php

namespace Axima\SmsGate;

/**
 * @author Matej Bukovsky <matejbukovsky@gmail.com>
 */
class Message
{
	/**
	 * @var string
	 */
	private $text;

	/**
	 * @var mixed[]
	 */
	private $phones;

	/**
	 * @var \DateTime
	 */
	private $sendAt;

	/**
	 * @var bool
	 */
	private $confirmation;

	public function __construct(string $text, array $phones = [])
	{
		$this->text = trim($text);
		$this->setPhones($phones);
	}

	private function validatePhone($phone)
	{
		if (!Validators::validateNumber($phone)) {
			throw new ClientException('Please provide a phone number in a valid format.');
		}
	}

	public function setSendAt(\DateTimeInterface $sendAt)
	{
		$this->sendAt = $sendAt;
	}

	public function setConfirmation($value)
	{
		$this->confirmation = $value;
	}

	public function setText($text)
	{
		$this->text = trim($text);
	}

	public function getText()
	{
		return $this->text;
	}

	public function setPhones($phones)
	{
		foreach ($phones as $phone) {
			$this->validatePhone($phone);
		}
		$this->phones = $phones;
	}

	public function addPhone($phone)
	{
		$this->validatePhone($phone);
		$this->phones[] = $phone;
	}

	public function getPhones()
	{
		return $this->phones;
	}

	public function getSendAt()
	{
		return $this->sendAt;
	}

	public function getConfirmation()
	{
		return $this->confirmation;
	}

}
