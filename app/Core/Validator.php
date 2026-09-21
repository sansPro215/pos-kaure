<?php

namespace App\Core;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): self
    {
        $validator = new self($data, $rules);
        $validator->validate();
        return $validator;
    }

    public function validate(): bool
    {
        foreach ($this->rules as $field => $fieldRules) {
            if (is_string($fieldRules)) {
                $fieldRules = explode('|', $fieldRules);
            }

            $value = $this->data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $param = null;
                if (strpos($rule, ':') !== false) {
                    [$rule, $param] = explode(':', $rule, 2);
                }

                switch ($rule) {
                    case 'required':
                        if ($value === null || trim((string)$value) === '') {
                            $this->addError($field, "Kolom {$field} wajib diisi.");
                        }
                        break;

                    case 'numeric':
                        if ($value !== null && $value !== '' && !is_numeric($value)) {
                            $this->addError($field, "Kolom {$field} harus berupa angka.");
                        }
                        break;

                    case 'min':
                        if (is_numeric($value)) {
                            if ((float)$value < (float)$param) {
                                $this->addError($field, "Kolom {$field} minimal {$param}.");
                            }
                        } elseif (is_string($value)) {
                            if (mb_strlen($value) < (int)$param) {
                                $this->addError($field, "Kolom {$field} minimal berisi {$param} karakter.");
                            }
                        }
                        break;

                    case 'max':
                        if (is_numeric($value)) {
                            if ((float)$value > (float)$param) {
                                $this->addError($field, "Kolom {$field} maksimal {$param}.");
                            }
                        } elseif (is_string($value)) {
                            if (mb_strlen($value) > (int)$param) {
                                $this->addError($field, "Kolom {$field} maksimal berisi {$param} karakter.");
                            }
                        }
                        break;

                    case 'in':
                        $allowed = explode(',', $param);
                        if ($value !== null && $value !== '' && !in_array((string)$value, $allowed, true)) {
                            $this->addError($field, "Pilihan pada {$field} tidak valid.");
                        }
                        break;
                }
            }
        }

        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }
        $firstField = array_key_first($this->errors);
        return $this->errors[$firstField][0] ?? null;
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
}
