<?php

namespace App\Helpers;

class Validator
{
    protected $data;
    protected $rules;
    protected $customMessages;
    protected $attributes;
    protected $errors;

    public function __construct($data, $rules, $customMessages = [], $attributes = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
        $this->attributes = $attributes;
        $this->errors = [];
    }

    public function fails()
    {
        foreach ($this->rules as $field => $ruleset) {
            $rules = explode('|', $ruleset);
            $value = $this->getValueByDotNotation($field);

            // required değilse ve değer boşsa diğer kuralları atla
            if ((is_null($value) || $value === '') && !in_array('required', $rules)) {
                continue;
            }

            foreach ($rules as $index => $rule) {
                if (!$this->validateRule($field, $rule)) {
                    $this->errors[$field][] = $this->customMessages[$field][$index] ?? "$field is invalid";
                }
            }
        }
        return !empty($this->errors);
    }

    protected function validateRule($field, $rule)
    {
        $value = $this->getValueByDotNotation($field);

        if (strpos($rule, ':')) {
            [$ruleName, $parameter] = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $parameter = null;
        }

        switch ($ruleName) {
            case 'required':
                return !is_null($value) && $value !== '';
            case 'regex':
                return !is_null($value) && preg_match($parameter, $value);
            case 'max':
                return !is_null($value) && mb_strlen($value) <= (int)$parameter;
            case 'min':
                return !is_null($value) && mb_strlen($value) >= (int)$parameter;
            case 'email':
                return !is_null($value) && filter_var($value, FILTER_VALIDATE_EMAIL);
            case 'numeric':
                return !is_null($value) && is_numeric($value);
            case 'digits':
                return !is_null($value) && ctype_digit((string)$value) && strlen((string)$value) == (int)$parameter;
            case 'starts_with':
                return !is_null($value) && strpos($value, $parameter) === 0;
            case 'array':
                return !is_null($value) && is_array($value);
            case 'boolean':
                return !is_null($value) && (is_bool($value) || in_array($value, ['true', 'false', '1', '0'], true));
            case 'exists':
                return !is_null($value) && isset($this->data[$parameter]) && in_array($value, $this->data[$parameter]);
            case 'in':
                $allowedValues = explode(',', $parameter);
                return !is_null($value) && in_array($value, $allowedValues, true);
            default:
                return true;
        }
    }

    public function addRule($field, $rule)
    {
        if (isset($this->rules[$field])) {
            $this->rules[$field] .= '|' . $rule;
        } else {
            $this->rules[$field] = $rule;
        }
    }

    public function addCustomMessage($field, $messages)
    {
        if (!isset($this->customMessages[$field])) {
            $this->customMessages[$field] = [];
        }

        if (is_array($messages)) {
            foreach ($messages as $message) {
                $this->customMessages[$field][] = $message;
            }
        } else {
            $this->customMessages[$field][] = $messages;
        }
    }

    protected function getValueByDotNotation($key)
    {
        $segments = explode('.', $key);
        $value = $this->data;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function errors()
    {
        $errorMessages = [];
        foreach ($this->errors as $field => $messages) {
            $errorMessages[$field] = $messages[0]; // İlk hatayı döndür
        }
        return $errorMessages;
    }
}
