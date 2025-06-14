# Validation System

A comprehensive validation system for the CosmicHub framework that provides flexible, extensible, and easy-to-use validation capabilities.

## Features

- **Rule-based validation**: Extensible rule system with built-in rules
- **Custom error messages**: Customizable error messages with placeholders
- **Attribute aliases**: Custom field names for user-friendly error messages
- **Fluent interface**: Easy-to-use API for validation operations
- **Exception handling**: Structured validation exceptions
- **Statistics tracking**: Performance monitoring and usage statistics
- **Helper functions**: Global helper functions for quick validation

## Basic Usage

### Using the Validation Manager

```php
use App\Core\Validation\ValidationManager;

$validator = app('validator');

// Create a validator instance
$validation = $validator->make([
    'email' => 'user@example.com',
    'name' => 'John Doe',
    'age' => 25
], [
    'email' => 'required|email',
    'name' => 'required|alpha|min_length:2',
    'age' => 'required|integer'
]);

// Check if validation passes
if ($validation->passes()) {
    $validatedData = $validation->validated();
    // Process validated data
} else {
    $errors = $validation->getErrors();
    // Handle validation errors
}
```

### Using Helper Functions

```php
// Quick validation
try {
    $validatedData = validate([
        'email' => 'user@example.com',
        'password' => 'secret123'
    ], [
        'email' => 'required|email',
        'password' => 'required|min_length:8'
    ]);
} catch (ValidationException $e) {
    $errors = $e->getErrors();
}

// Check if validation passes
if (validation_passes($data, $rules)) {
    // Validation passed
}

// Get validation errors
$errors = validation_errors($data, $rules);
```

## Available Rules

### Basic Rules

- `required` - Field must be present and not empty
- `email` - Field must be a valid email address
- `numeric` - Field must be numeric
- `integer` - Field must be an integer
- `alpha` - Field must contain only letters
- `alpha_num` - Field must contain only letters and numbers

### Length Rules

- `min_length:value` - Field must have at least the specified length
- `max_length:value` - Field must not exceed the specified length

### Email Validation Options

```php
'email' => 'required|email:strict,dns'
```

- `strict` - More strict email format validation
- `dns` - Validate domain via DNS lookup

## Custom Error Messages

```php
$validator = validator($data, $rules, [
    'email.required' => 'Please provide your email address',
    'email.email' => 'Please provide a valid email address',
    'password.min_length' => 'Password must be at least :param characters long'
]);
```

## Custom Attribute Names

```php
$validator = validator($data, $rules, [], [
    'email' => 'Email Address',
    'first_name' => 'First Name'
]);
```

## Creating Custom Rules

### Step 1: Create Rule Class

```php
namespace App\Validation\Rules;

use App\Core\Validation\Rules\AbstractRule;

class CustomRule extends AbstractRule
{
    protected string $name = 'custom';
    protected string $message = 'The :attribute field is invalid.';
    
    public function validate(string $field, $value, array $parameters, array $data): bool
    {
        // Your validation logic here
        return true; // or false
    }
}
```

### Step 2: Register the Rule

```php
$validator = app('validator');
$validator->extend('custom', CustomRule::class);
```

## Validation Exception Handling

```php
use App\Core\Validation\Exceptions\ValidationException;

try {
    $validatedData = validate($data, $rules);
} catch (ValidationException $e) {
    // Get all errors
    $allErrors = $e->getErrors();
    
    // Get first error
    $firstError = $e->getFirstError();
    
    // Get errors for specific field
    $emailErrors = $e->getFieldErrors('email');
    
    // Check if field has errors
    if ($e->hasError('email')) {
        // Handle email errors
    }
    
    // Convert to array
    $errorArray = $e->toArray();
    
    // Convert to JSON
    $errorJson = $e->toJson();
}
```

## Advanced Usage

### Conditional Validation

```php
$validator = validator($data, [
    'email' => 'required|email',
    'phone' => function($field, $value, $data) {
        // Custom validation logic
        if (empty($data['email']) && empty($value)) {
            return 'Either email or phone is required';
        }
        return true;
    }
]);
```

### Validation Statistics

```php
$validator = app('validator');
$stats = $validator->getStats();

echo "Total validations: " . $stats['total_validations'];
echo "Failed validations: " . $stats['failed_validations'];
echo "Success rate: " . $stats['success_rate'] . "%";
```

## Integration with Controllers

```php
namespace App\Controllers;

use App\Core\Controller\BaseController;
use App\Core\Validation\Exceptions\ValidationException;

class UserController extends BaseController
{
    public function store()
    {
        try {
            $validatedData = validate($this->request->all(), [
                'name' => 'required|alpha|min_length:2',
                'email' => 'required|email',
                'password' => 'required|min_length:8'
            ]);
            
            // Create user with validated data
            $user = User::create($validatedData);
            
            return $this->success('User created successfully', $user);
            
        } catch (ValidationException $e) {
            return $this->error('Validation failed', $e->getErrors(), 422);
        }
    }
}
```

## Configuration

The validation system can be configured through the service provider or by extending the validation manager:

```php
// In a service provider
public function boot()
{
    $validator = $this->app->make('validator');
    
    // Add custom rules
    $validator->extend('phone', PhoneRule::class);
    
    // Set custom messages
    $validator->setMessages([
        'phone' => 'Please provide a valid phone number'
    ]);
    
    // Set custom attributes
    $validator->setAttributes([
        'phone_number' => 'Phone Number'
    ]);
}
```

## Performance Considerations

- Rules are instantiated only when needed
- Validation stops on first failure for rules marked with `stopOnFailure`
- Statistics tracking can be disabled in production for better performance
- Use appropriate rule ordering (put faster rules first)

## Error Message Placeholders

- `:attribute` - The field name or custom attribute
- `:value` - The field value that failed validation
- `:param` - The first rule parameter
- `:param0`, `:param1`, etc. - Specific rule parameters

## Best Practices

1. **Use specific rules**: Use the most specific rule for your validation needs
2. **Order rules efficiently**: Put required and basic type checks first
3. **Custom messages**: Provide user-friendly error messages
4. **Attribute names**: Use readable field names in error messages
5. **Exception handling**: Always handle validation exceptions appropriately
6. **Performance**: Consider rule performance for high-traffic applications

## Testing

```php
use PHPUnit\Framework\TestCase;
use App\Core\Validation\ValidationManager;

class ValidationTest extends TestCase
{
    public function testEmailValidation()
    {
        $validator = new ValidationManager(app());
        
        $result = $validator->make([
            'email' => 'invalid-email'
        ], [
            'email' => 'required|email'
        ]);
        
        $this->assertTrue($result->fails());
        $this->assertArrayHasKey('email', $result->getErrors());
    }
}
```