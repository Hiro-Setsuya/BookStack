<?php

/**
 * Reusable Bootstrap Floating Input Component
 * Generates a Bootstrap 5 floating label input field
 * 
 * @param array $config Configuration array with the following keys:
 *   - type: string (default: 'text') - Input type (text, email, password, number, tel, date, etc.)
 *   - name: string (required) - Input name attribute
 *   - id: string (optional) - Input id attribute (defaults to name if not provided)
 *   - label: string (required) - Label text to display
 *   - value: string (optional) - Pre-filled value
 *   - placeholder: string (optional) - Placeholder text (required for floating labels to work)
 *   - required: bool (default: false) - Whether field is required
 *   - disabled: bool (default: false) - Whether field is disabled
 *   - readonly: bool (default: false) - Whether field is readonly
 *   - class: string (optional) - Additional CSS classes for the wrapper div
 *   - input_class: string (optional) - Additional CSS classes for the input element
 *   - min: string|int (optional) - Min value for number/date inputs
 *   - max: string|int (optional) - Max value for number/date inputs
 *   - step: string (optional) - Step value for number inputs
 *   - pattern: string (optional) - Regex pattern for validation
 *   - maxlength: int (optional) - Maximum character length
 *   - autocomplete: string (optional) - Autocomplete attribute
 *   - attributes: array (optional) - Additional custom attributes as key-value pairs
 * 
 * @return void Outputs the HTML directly
 */
function renderFloatingInput($config)
{
  // Extract configuration with defaults
  $type = $config['type'] ?? 'text';
  $name = $config['name'] ?? '';
  $id = $config['id'] ?? $name;
  $label = $config['label'] ?? '';
  $value = $config['value'] ?? '';
  $placeholder = $config['placeholder'] ?? $label; // Use label as placeholder if not provided
  $required = isset($config['required']) && $config['required'];
  $disabled = isset($config['disabled']) && $config['disabled'];
  $readonly = isset($config['readonly']) && $config['readonly'];
  $wrapperClass = $config['class'] ?? 'mb-3';
  $inputClass = $config['input_class'] ?? '';
  $attributes = $config['attributes'] ?? [];

  // Build additional attributes string
  $attrString = '';

  // Add optional attributes
  if (isset($config['min'])) $attrString .= ' min="' . htmlspecialchars($config['min']) . '"';
  if (isset($config['max'])) $attrString .= ' max="' . htmlspecialchars($config['max']) . '"';
  if (isset($config['step'])) $attrString .= ' step="' . htmlspecialchars($config['step']) . '"';
  if (isset($config['pattern'])) $attrString .= ' pattern="' . htmlspecialchars($config['pattern']) . '"';
  if (isset($config['maxlength'])) $attrString .= ' maxlength="' . htmlspecialchars($config['maxlength']) . '"';
  if (isset($config['autocomplete'])) $attrString .= ' autocomplete="' . htmlspecialchars($config['autocomplete']) . '"';

  // Add custom attributes
  foreach ($attributes as $key => $val) {
    $attrString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
  }

  // Build boolean attributes
  $requiredAttr = $required ? ' required' : '';
  $disabledAttr = $disabled ? ' disabled' : '';
  $readonlyAttr = $readonly ? ' readonly' : '';

  // Sanitize values for output
  $safeId = htmlspecialchars($id);
  $safeName = htmlspecialchars($name);
  $safeValue = htmlspecialchars($value);
  $safeLabel = htmlspecialchars($label);
  $safePlaceholder = htmlspecialchars($placeholder);
  $safeType = htmlspecialchars($type);

  // Output the HTML using Bootstrap's form-floating class
  echo '<div class="form-floating ' . htmlspecialchars($wrapperClass) . '">';
  echo '<input ';
  echo 'type="' . $safeType . '" ';
  echo 'class="form-control ' . htmlspecialchars($inputClass) . '" ';
  echo 'id="' . $safeId . '" ';
  echo 'name="' . $safeName . '" ';
  echo 'placeholder="' . $safePlaceholder . '" ';
  if ($safeValue !== '') {
    echo 'value="' . $safeValue . '" ';
  }
  echo $requiredAttr;
  echo $disabledAttr;
  echo $readonlyAttr;
  echo $attrString;
  echo ' />';
  echo '<label for="' . $safeId . '">' . $safeLabel . '</label>';
  echo '</div>';
}

/**
 * Outputs the required CSS for Bootstrap floating inputs with custom green focus color
 * Include this in your <head> section or style.css
 * 
 * @return void Outputs the CSS directly
 */
function renderFloatingInputStyles()
{
  echo '<style>
      /* Custom focus color for inputs and labels */
      .form-floating .form-control:focus {
        border-color: #1fd26a;
        box-shadow: none;
      }

      .form-floating .form-control:focus ~ label {
        color: #1fd26a;
      }
      
      /* Invalid state styling */
      .form-floating .form-control.is-invalid {
        border-color: #dc3545;
      }
      
      .form-floating .form-control.is-invalid:focus ~ label {
        color: #dc3545;
      }
    </style>';
}

/**
 * Example usage:
 * 
 * // In your HTML head section:
 * <?php renderFloatingInputStyles(); ?>
 * 
 * // In your form:
 * <?php
 * renderFloatingInput([
 *     'type' => 'email',
 *     'name' => 'email',
 *     'id' => 'email',
 *     'label' => 'Email',
 *     'placeholder' => 'Email',
 *     'required' => true,
 *     'autocomplete' => 'email'
 * ]);
 * 
 * renderFloatingInput([
 *     'type' => 'password',
 *     'name' => 'password',
 *     'id' => 'password',
 *     'label' => 'Password',
 *     'placeholder' => 'Password',
 *     'required' => true,
 *     'autocomplete' => 'current-password'
 * ]);
 * ?>
 */
