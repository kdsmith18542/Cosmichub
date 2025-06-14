<?php

namespace App\Core\Http\Requests;

use App\Core\Http\Request;
use App\Core\Validation\Validator;
use App\Exceptions\ValidationException;

/**
 * Base Form Request class for handling form validation
 */
abstract class FormRequest extends Request
{
    /**
     * @var array The validated data
     */
    protected $validated = [];

    /**
     * @var Validator The validator instance
     */
    protected $validator;

    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    abstract public function rules(): array;

    /**
     * Get custom messages for validator errors
     *
     * @return array
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Get custom attributes for validator errors
     *
     * @return array
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Validate the request
     *
     * @return array The validated data
     * @throws ValidationException
     */
    public function validate(): array
    {
        if (!$this->authorize()) {
            throw new ValidationException('Unauthorized request');
        }

        $this->validator = new Validator(
            $this->all(),
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );

        if ($this->validator->fails()) {
            throw new ValidationException(
                'Validation failed',
                $this->validator->errors()
            );
        }

        $this->validated = $this->validator->validated();
        return $this->validated;
    }

    /**
     * Get the validated data
     *
     * @return array
     */
    public function validated(): array
    {
        if (empty($this->validated)) {
            $this->validate();
        }

        return $this->validated;
    }

    /**
     * Get a specific validated field
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function validatedInput(string $key, $default = null)
    {
        $validated = $this->validated();
        return $validated[$key] ?? $default;
    }

    /**
     * Prepare the data for validation
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Override in child classes to modify data before validation
    }

    /**
     * Handle a passed validation attempt
     *
     * @return void
     */
    protected function passedValidation(): void
    {
        // Override in child classes to handle successful validation
    }

    /**
     * Handle a failed validation attempt
     *
     * @param Validator $validator
     * @return void
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new ValidationException(
            'The given data was invalid.',
            $validator->errors()
        );
    }

    /**
     * Get the validator instance
     *
     * @return Validator|null
     */
    public function getValidator(): ?Validator
    {
        return $this->validator;
    }
}