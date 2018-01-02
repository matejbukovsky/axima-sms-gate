<?php
/**
 * @author Tomáš Blatný
 * @author Matej Bukovsky matejbukovsky@gmail.com
 */

namespace Axima\SmsGate;

use GuzzleHttp\ClientInterface;
use SimpleXMLElement;

class Client
{

	/** @var string */
	public static $sendMessageUrl = 'https://smsgateapi.sluzba.cz/apixml30/receiver';

	/** @var string */
	public static $deliveryMessageUrl = 'https://smsgateapi.sluzba.cz/apixml30/sender';

	/** @var string */
	public static $confirmMessageUrl = 'https://smsgateapi.sluzba.cz/apixml30/confirm';

	/** @var string */
	public static $infoUrl = 'https://smsgateapi.sluzba.cz/apixml30/info/credit';

	/** @var ClientInterface */
	private $guzzleClient;

	/** @var string */
	private $login;

	/** @var string */
	private $password;

	public function __construct(ClientInterface $guzzleClient, $login, $password)
	{
		$this->guzzleClient = $guzzleClient;
		$this->login = $login;
		$this->password = $password;
	}

	/**
	 * @param \Axima\SmsGate\Message $message
	 * @return array
	 * @throws ClientException
	 * @throws SmsGateException
	 */
	public function sendSms(Message $message)
	{
		$text = $message->getText();
		$result = [];
		if ($text === '') {
			throw new ClientException('Please provide a non-empty text message.');
		}

		$sendAt = $message->getSendAt();
		if ($sendAt) {
			$sendAt = $sendAt->format('YmdHis');
		}

		$requestConfirmation = $message->getConfirmation();
		if ($requestConfirmation === TRUE) {
			$requestConfirmation = 20;
		} elseif ($requestConfirmation === FALSE) {
			$requestConfirmation = 0;
		} elseif ($requestConfirmation !== NULL) {
			throw new ClientException('Request confirmation parameter may be only TRUE/FALSE or NULL.');
		}

		foreach ($message->getPhones() as $phone) {
			$response = $this->guzzleClient->post(self::$sendMessageUrl, array(
				'query' => array(
					'login' => $this->login,
					'password' => $this->password,
				),
				'headers' => array(
					'Content-type' => 'text/xml'
				),
				'body' => $this->getXml($text, $phone, $requestConfirmation, $sendAt),
			));
			if ($response->getStatusCode() !== 200) {
				$xml = simplexml_load_string($response->getBody()->getContents());
				throw new SmsGateException((string) $xml->message, $response->getStatusCode());
			}

			$xml = simplexml_load_string($response->getBody()->getContents());
			$result[] = $this->xml2Array($xml);
		}

		return $result;
	}

	/**
	 * @return string[]
	 * @throws SmsGateException
	 */
	public function getDeliveryReports()
	{
		$response = $this->guzzleClient->post(self::$deliveryMessageUrl, array(
			'query' => array(
				'login' => $this->login,
				'password' => $this->password,
				'query_answer_message' => 0,
				'query_delivery_report' => 1,
				'count' => 30,
			),
			'headers' => array(
				'Content-type' => 'text/xml'
			),
		));

		if ($response->getStatusCode() !== 200) {
			$xml = simplexml_load_string($response->getBody()->getContents());
			throw new SmsGateException((string) $xml->message, $response->getStatusCode());
		}

		$xml = simplexml_load_string($response->getBody()->getContents());

		$result = array();
		foreach ($xml as $message) {
			$result[(string) $message->id] = (string) $message->delivery_timestamp;
		}
		return $result;
	}

	/**
	 * @param string $id
	 * @throws SmsGateException
	 */
	public function confirmDeliveryReport($id)
	{
		$response = $this->guzzleClient->get(self::$confirmMessageUrl, array(
			'query' => array(
				'login' => $this->login,
				'password' => $this->password,
				'type' => 'delivery_report',
				'id' => $id,
			),
			'headers' => array(
				'Content-type' => 'text/xml'
			),
		));

		$stringResponse = (string) $response->getBody();

		if (!trim($stringResponse)) {
			return TRUE;
		} else {
			return FALSE;
		}
		if ($response->getStatusCode() !== 200) {
			throw new SmsGateException('Error while sending request.', $response->getStatusCode());
		}
	}

	/**
	 * @return array
	 * @throws SmsGateException
	 */
	public function getAccountStatus()
	{
		$response = $this->guzzleClient->get(self::$infoUrl, array(
			'query' => array(
				'login' => $this->login,
				'password' => $this->password,
			),
			'headers' => array(
				'Content-type' => 'text/xml'
			),
		));

		if ($response->getStatusCode() !== 200) {
			throw new SmsGateException('Error while sending request.', $response->getStatusCode());
		}

		$xml = simplexml_load_string($response->getBody()->getContents());
		return $this->xml2Array($xml);
	}

	/**
	 * @param string $text
	 * @param string $number
	 * @param string|NULL $drRequest
	 * @param string|NULL $sendAt
	 * @return string
	 * @throws ClientException
	 */
	private function getXml($text, $number, $drRequest = NULL, $sendAt = NULL)
	{
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><outgoing_message/>');
		$xml->addChild('text', $text);
		$xml->addChild('recipient', $number);
		if ($drRequest !== NULL) {
			$xml->addChild('dr_request', $drRequest);
		}
		if ($sendAt !== NULL) {
			$xml->addChild('send_at', $sendAt);
		}
		$result = $xml->asXML();
		if ($result === FALSE) {
			throw new ClientException('Unable to create XML document.');
		}
		return $result;
	}

	private function xml2Array(\SimpleXMLElement $xml)
	{
		$out = [];
		foreach ((array) $xml as $index => $node) {
			$out[$index] = is_object($node) ? $this->xml2Array($node) : $node;
		}

		return $out;
	}

}
