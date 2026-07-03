<?php

namespace GenAI\GoogleForm;

/**
 * Submits to a Google Form from the server.
 *
 *   $form = new GoogleForm('1FAIpQLSf...', array(
 *       'name'  => 'entry.111111111',
 *       'email' => 'entry.222222222',
 *   ));
 *   $form->submit(array('name' => 'Linh', 'email' => 'linh@example.com'));
 *
 * Maps your field names to the form's entry.NNN ids and POSTs to the form's
 * formResponse endpoint. Best-effort: a short timeout, and it returns false
 * (never throws) on a network error or an unset form id — so it won't break the
 * action that calls it (e.g. registration still succeeds if Google is down).
 *
 * Note: Google returns 200 even when it silently rejects a submission (a missing
 * required field), so "true" means "the request was sent", not "definitely
 * recorded" — map every required field and verify once in the form's responses.
 *
 * Compatible with PHP 5.3.29.
 */
class GoogleForm
{
    /** @var string */
    private $formId;

    /** @var array field name => entry.NNN id */
    private $map;

    /** @var int seconds */
    private $timeout;

    public function __construct($formId, $fieldMap = array(), $timeout = 5)
    {
        $this->formId  = (string) $formId;
        $this->map     = $fieldMap;
        $this->timeout = (int) $timeout;
    }

    /**
     * Submit data keyed by your field names (mapped to entry ids via the map).
     *
     * @param array $data field name => value (a value may be an array for checkboxes)
     * @return bool whether the request was sent
     */
    public function submit($data)
    {
        $entry = array();
        foreach ($this->map as $field => $entryId) {
            if (array_key_exists($field, $data)) {
                $entry[$entryId] = $data[$field];
            }
        }

        return $this->submitRaw($entry);
    }

    /**
     * Submit raw entry data (entry.NNN => value), bypassing the map.
     *
     * @param array $entryData
     * @return bool
     */
    public function submitRaw($entryData)
    {
        if ($this->formId === '') {
            return false; // not configured -> no-op
        }

        $url  = 'https://docs.google.com/forms/d/e/' . $this->formId . '/formResponse';
        $body = $this->encode($entryData);

        return $this->post($url, $body);
    }

    /** Build an application/x-www-form-urlencoded body, repeating keys for arrays. */
    private function encode($entryData)
    {
        $pairs = array();
        foreach ($entryData as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $pairs[] = urlencode($key) . '=' . urlencode($v);
                }
            } else {
                $pairs[] = urlencode($key) . '=' . urlencode($value);
            }
        }

        return implode('&', $pairs);
    }

    private function post($url, $body)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER     => array('Content-Type: application/x-www-form-urlencoded'),
            ));
            $result = curl_exec($ch);
            $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $result !== false && $code >= 200 && $code < 400;
        }

        $ctx = stream_context_create(array('http' => array(
            'method'        => 'POST',
            'header'        => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content'       => $body,
            'timeout'       => $this->timeout,
            'ignore_errors' => true,
        )));

        return @file_get_contents($url, false, $ctx) !== false;
    }
}
