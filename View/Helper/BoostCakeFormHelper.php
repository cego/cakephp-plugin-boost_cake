<?php
App::uses('FormHelper', 'View/Helper');
App::uses('Set', 'Utility');

class BoostCakeFormHelper extends FormHelper {

	public $helpers = array('Html' => array(
		'className' => 'BoostCake.BoostCakeHtml'
	));

	protected $_divOptions = array();

	protected $_inputOptions = array();

	protected $_inputType = null;

	protected $_fieldName = null;

/**
 * Overwrite FormHelper::input()
 *
 * - Generates a form input element complete with label and wrapper div
 *
 * ### Options
 *
 * See each field type method for more information. Any options that are part of
 * $attributes or $options for the different **type** methods can be included in `$options` for input().i
 * Additionally, any unknown keys that are not in the list below, or part of the selected type's options
 * will be treated as a regular html attribute for the generated input.
 *
 * - `type` - Force the type of widget you want. e.g. `type => 'select'`
 * - `label` - Either a string label, or an array of options for the label. See FormHelper::label().
 * - `div` - Either `false` to disable the div, or an array of options for the div.
 *	See HtmlHelper::div() for more options.
 * - `options` - For widgets that take options e.g. radio, select.
 * - `error` - Control the error message that is produced. Set to `false` to disable any kind of error reporting (field
 *    error and error messages).
 * - `errorMessage` - Boolean to control rendering error messages (field error will still occur).
 * - `empty` - String or boolean to enable empty select box options.
 * - `before` - Content to place before the label + input.
 * - `after` - Content to place after the label + input.
 * - `between` - Content to place between the label + input.
 * - `format` - Format template for element order. Any element that is not in the array, will not be in the output.
 *	- Default input format order: array('before', 'label', 'between', 'input', 'after', 'error')
 *	- Default checkbox format order: array('before', 'input', 'between', 'label', 'after', 'error')
 *	- Hidden input will not be formatted
 *	- Radio buttons cannot have the order of input and label elements controlled with these settings.
 *
 * Added options
 * - `wrapInput` - Either `false` to disable the div wrapping input, or an array of options for the div.
 *	See HtmlHelper::div() for more options.
 * - `checkboxDiv` - Wrap input checkbox tag's class.
 * - `beforeInput` - Content to place before the input.
 * - `afterInput` - Content to place after the input.
 * - `errorClass` - Wrap input tag's error message class.
 *
 * @param string $fieldName This should be "Modelname.fieldname"
 * @param array $options Each type of input takes different options.
 * @return string Completed form widget.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#creating-form-elements
 */
	public function input($fieldName, $options = array()) {
		$this->_fieldName = $fieldName;

		$default = array(
			'error' => array(
				'attributes' => array(
					'wrap' => 'span',
					'class' => 'help-block text-danger'
				)
			),
			'wrapInput' => array(
				'tag' => 'div'
			),
			'checkboxDiv' => 'form-check',
			'beforeInput' => '',
			'afterInput' => '',
			'errorClass' => 'has-error error'
		);

		$inputDefaultSetting = array(
			'div' => 'form-group row',
			'label' => array(
				'class' => 'col-xs-12 col-md-3 form-control-label'
			),
			'wrapInput' => 'col-xs-12 col-md-9',
			'class' => 'form-control form-control-static'
		);

		if (isset($this->_inputDefaults)) {
			$inputDefaultSetting = array_merge($inputDefaultSetting, $this->_inputDefaults);
			$inputDefaults = $this->inputDefaults($inputDefaultSetting);
		} else {
			$inputDefaults = $this->inputDefaults($inputDefaultSetting);
		}


		if (isset($options['label']) && !is_array($options['label']) && $options['label'] !== false) {
			$labelText = $options['label'];
			$options['label'] = array('text' => $labelText);
		} else {
			$options['label'] = false;
		}

		$options = Hash::merge(
			$default,
			$this->_inputDefaults,
			$options
		);

		$this->_inputOptions = $options;

		$options['error'] = false;
		if (isset($options['wrapInput'])) {
			unset($options['wrapInput']);
		}
		if (isset($options['checkboxDiv'])) {
			unset($options['checkboxDiv']);
		}
		if (isset($options['beforeInput'])) {
			unset($options['beforeInput']);
		}
		if (isset($options['afterInput'])) {
			unset($options['afterInput']);
		}
		if (isset($options['errorClass'])) {
			unset($options['errorClass']);
		}

		$html = parent::input($fieldName, $options);

		if ($this->_inputType === 'checkbox') {
			$labelClass = 'form-check-label';
			$html = str_replace($options['label']['class'], $labelClass, $html);
			$inputClass = 'form-check-input';
			$html = str_replace($options['class'], $inputClass, $html);
			if (isset($options['before'])) {
				$html = str_replace($options['before'], '%before%', $html);
			}
			$regex = '/(<label.*?>)(.*?<\/label>)/';
			if (preg_match($regex, $html, $label)) {
				$label = str_replace('$', '\$', $label);
				$html = preg_replace($regex, '', $html);
				$html = preg_replace(
					'/(<input type="checkbox".*?>)/',
					$label[1] . '$1 ' . $label[2],
					$html
				);
			}
			if (isset($options['before'])) {
				$html = str_replace('%before%', $options['before'], $html);
			}
		}

		if ($this->_inputType === 'datetime' || $this->_inputType === 'date' || $this->_inputType === 'time') {
			$class = $inputDefaults['class'] . ' js-date-time-picker ' . $this->_inputType;
			$html = str_replace($options['class'], $class, $html);
		}

		if ($this->_inputType === 'select') {
			$class = $inputDefaults['class'] . ' c-select';
			$html = str_replace($options['class'], $class, $html);
		}

		if ($this->_inputType === 'submit') {
			$class = 'btn btn-primary';
			$html = str_replace($options['class'], $class, $html);
		}

		return $html;
	}

/**
 * Overwrite FormHelper::dateTime()
 * Converts datetime to only one inputfield
 * Attaches a datepicker to all datetime fields
 */

	public function dateTime($fieldName, $dateFormat = 'DMY', $timeFormat = '12', $attributes = array()) {

		$attributes += array('empty' => true, 'value' => null);
		$year = $month = $day = $hour = $min = $meridian = null;

		if (empty($attributes['value'])) {
			$attributes = $this->value($attributes, $fieldName);
		}

		// Check for missing value
		if ($attributes['value'] === null && $attributes['empty'] != true) {
			$value = time();
		} else {
			$value = $attributes['value'];
		}

		// Convert timestamp to something strtotime can read
		if (is_numeric($value) && $value > 1000000000) {
			$value = '@'.$value;
		}

		// If value is array (the format submitted by forms), convert to strtotime readable string
		if (is_array($value) && isset($value['date']) && $value['date']) {
			$value = '@'.strtotime($value['date'] . (isset($value['time']) ? ' ' . $value['time'] : ''));
		}

		// If value is array (the format submitted by forms), convert to strtotime readable string
		if (is_array($value) && !isset($value['date']) && isset($value['time']) && $value['time']) {
			$value = '@'.strtotime($value['date'] . (isset($value['time']) ? ' ' . $value['time'] : ''));
		}

		// If value is empty, or if the date or time keys are empty
		if ($value == '' || (is_array($value) && (isset($value['date']) && $value['date'] == '' || isset($value['time']) && $value['time'] == ''))) {
			$value = null;
		}

		$output = '';

		unset($attributes['value']);
		unset($attributes['empty']);

		if ($dateFormat !== null) {

			if ($value === null) {
				$output .= $this->text($fieldName, array(
					'value' => '',
					'class' => $attributes['class']
				));
			} else {
				$attributes['value'] = $value;
				// Generate output fot the textfield
				$output .= $this->text($fieldName, $attributes);
			}

		}

		return $output;
	}
/**
 * Generate a set of inputs for `$fields`. If $fields is null the fields of current model
 * will be used.
 *
 * You can customize individual inputs through `$fields`.
 * ```
 *	$this->Form->inputs(array(
 *		'name' => array('label' => 'custom label')
 *	));
 * ```
 *
 * In addition to controller fields output, `$fields` can be used to control legend
 * and fieldset rendering.
 * `$this->Form->inputs('My legend');` Would generate an input set with a custom legend.
 * Passing `fieldset` and `legend` key in `$fields` array has been deprecated since 2.3,
 * for more fine grained control use the `fieldset` and `legend` keys in `$options` param.
 *
 * @param array $fields An array of fields to generate inputs for, or null.
 * @param array $blacklist A simple array of fields to not create inputs for.
 * @param array $options Options array. Valid keys are:
 * - `fieldset` Set to false to disable the fieldset. If a string is supplied it will be used as
 *    the class name for the fieldset element.
 * - `legend` Set to false to disable the legend for the generated input set. Or supply a string
 *    to customize the legend text.
 * @return string Completed form inputs.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::inputs
 */
	public function inputs($fields = null, $blacklist = null, $options = array()) {
		$fieldset = $legend = false;
		$modelFields = array();
		$model = $this->model();
		if ($model) {
			$modelFields = array_keys((array)$this->_introspectModel($model, 'fields'));
		}
		if (is_array($fields)) {
			if (array_key_exists('legend', $fields) && !in_array('legend', $modelFields)) {
				$legend = $fields['legend'];
				unset($fields['legend']);
			}

			if (isset($fields['fieldset']) && !in_array('fieldset', $modelFields)) {
				$fieldset = $fields['fieldset'];
				unset($fields['fieldset']);
			}
		} elseif ($fields !== null) {
			$fieldset = $legend = $fields;
			if (!is_bool($fieldset)) {
				$fieldset = true;
			}
			$fields = array();
		}

		if (isset($options['legend'])) {
			$legend = $options['legend'];
		}
		if (isset($options['fieldset'])) {
			$fieldset = $options['fieldset'];
		}

		if (empty($fields)) {
			$fields = $modelFields;
		}

		if ($legend === true) {
			$actionName = __d('cake', 'New %s');
			$isEdit = (
				strpos($this->request->params['action'], 'update') !== false ||
				strpos($this->request->params['action'], 'edit') !== false
			);
			if ($isEdit) {
				$actionName = __d('cake', 'Edit %s');
			}
			$modelName = Inflector::humanize(Inflector::underscore($model));
			$legend = sprintf($actionName, __($modelName));
		}

		$out = null;
		foreach ($fields as $name => $options) {
			if (is_numeric($name) && !is_array($options)) {
				$name = $options;
				$options = array();
			}
			$entity = explode('.', $name);
			$blacklisted = (
				is_array($blacklist) &&
				(in_array($name, $blacklist) || in_array(end($entity), $blacklist))
			);
			if ($blacklisted) {
				continue;
			}
			$out .= $this->input($name, $options);
		}

		if (is_string($fieldset)) {
			$fieldsetClass = sprintf(' class="%s"', $fieldset);
		} else {
			$fieldsetClass = '';
		}

		if ($fieldset) {
			if ($legend) {
				$out = $this->Html->useTag('legend', $legend) . $out;
			}
			$out = $this->Html->useTag('fieldset', $fieldsetClass, $out);
		}
		return $out;
	}
/**
 * Overwrite FormHelper::_divOptions()
 *
 * - Generate inner and outer div options
 * - Generate div options for input
 *
 * @param array $options Options list.
 * @return array
 */
	protected function _divOptions($options) {
		$this->_inputType = $options['type'];

		$divOptions = array(
			'type' => $options['type'],
			'div' => $this->_inputOptions['wrapInput']
		);
		$this->_divOptions = parent::_divOptions($divOptions);

		$default = array('div' => array('class' => null));
		$options = Hash::merge($default, $options);
		$divOptions = parent::_divOptions($options);
		if ($this->tagIsInvalid() !== false) {
			$divOptions = $this->addClass($divOptions, $this->_inputOptions['errorClass']);
		}
		return $divOptions;
	}

/**
 * Overwrite FormHelper::_getInput()
 *
 * - Wrap `<div>` input element
 * - Generates an input element
 *
 * @param array $args The options for the input element
 * @return string The generated input element
 */
	protected function _getInput($args) {
		$input = parent::_getInput($args);
		if ($this->_inputType === 'checkbox' && $this->_inputOptions['checkboxDiv'] !== false) {
			$input = $this->Html->div($this->_inputOptions['checkboxDiv'], $input);
		}

		$beforeInput = $this->_inputOptions['beforeInput'];
		$afterInput = $this->_inputOptions['afterInput'];

		$error = null;
		$errorOptions = $this->_extractOption('error', $this->_inputOptions, null);
		$errorMessage = $this->_extractOption('errorMessage', $this->_inputOptions, true);
		if ($this->_inputType !== 'hidden' && $errorOptions !== false) {
			$errMsg = $this->error($this->_fieldName, $errorOptions);
			if ($errMsg && $errorMessage) {
				$error = $errMsg;
			}
		}

		$html = $beforeInput . $input . $afterInput . $error;

		if ($this->_divOptions) {
			$tag = $this->_divOptions['tag'];
			unset($this->_divOptions['tag']);
			$html = $this->Html->tag($tag, $html, $this->_divOptions);
		}

		return $html;
	}

/**
 * Overwrite FormHelper::_selectOptions()
 *
 * - If $attributes['style'] is `<input type="checkbox">` then replace `<label>` position
 * - Returns an array of formatted OPTION/OPTGROUP elements
 *
 * @param array $elements Elements to format.
 * @param array $parents Parents for OPTGROUP.
 * @param bool $showParents Whether to show parents.
 * @param array $attributes HTML attributes.
 * @return array
 */
	protected function _selectOptions($elements = array(), $parents = array(), $showParents = null, $attributes = array()) {
		$selectOptions = parent::_selectOptions($elements, $parents, $showParents, $attributes);

		if ($attributes['style'] === 'checkbox') {
			foreach ($selectOptions as $key => $option) {
				$option = preg_replace('/<div.*?>/', '', $option);
				$option = preg_replace('/<\/div>/', '', $option);
				if (preg_match('/>(<label.*?>)/', $option, $match)) {
					$class = $attributes['class'];
					if (preg_match('/.* class="(.*)".*/', $match[1], $classMatch)) {
						$class = $classMatch[1] . ' ' . $attributes['class'];
						$match[1] = str_replace(' class="' . $classMatch[1] . '"', '', $match[1]);
					}
					$option = $match[1] . preg_replace('/<label.*?>/', ' ', $option);
					$option = preg_replace('/(<label.*?)(>)/', '$1 class="' . $class . '"$2', $option);
				}
				$selectOptions[$key] = $option;
			}
		}

		return $selectOptions;
	}
/**
 * Creates a submit button element. This method will generate `<input />` elements that
 * can be used to submit, and reset forms by using $options. image submits can be created by supplying an
 * image path for $caption.
 *
 * ### Options
 *
 * - `div` - Include a wrapping div?  Defaults to true. Accepts sub options similar to
 *   FormHelper::input().
 * - `before` - Content to include before the input.
 * - `after` - Content to include after the input.
 * - `type` - Set to 'reset' for reset inputs. Defaults to 'submit'
 * - Other attributes will be assigned to the input element.
 *
 * ### Options
 *
 * - `div` - Include a wrapping div?  Defaults to true. Accepts sub options similar to
 *   FormHelper::input().
 * - Other attributes will be assigned to the input element.
 *
 * @param string $caption The label appearing on the button OR if string contains :// or the
 *  extension .jpg, .jpe, .jpeg, .gif, .png use an image if the extension
 *  exists, AND the first character is /, image is relative to webroot,
 *  OR if the first character is not /, image is relative to webroot/img.
 * @param array $options Array of options. See above.
 * @return string A HTML submit button
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::submit
 */
	public function submit($caption = null, $options = array()) {
		if (!is_string($caption) && empty($caption)) {
			$caption = __d('cake', 'Submit');
		}
		$out = null;
		$div = true;

		if (isset($options['div'])) {
			$div = $options['div'];
			unset($options['div']);
		}
		$options += array('class' => 'btn btn-primary', 'type' => 'submit', 'before' => null, 'after' => null, 'secure' => false);
		$divOptions = array('tag' => 'div');

		if ($div === true) {
			$divOptions['class'] = 'submit';
		} elseif ($div === false) {
			unset($divOptions);
		} elseif (is_string($div)) {
			$divOptions['class'] = $div;
		} elseif (is_array($div)) {
			$divOptions = array_merge(array('class' => 'submit', 'tag' => 'div'), $div);
		}

		if (isset($options['name'])) {
			$name = str_replace(array('[', ']'), array('.', ''), $options['name']);
			$this->_secure($options['secure'], $name);
		}
		unset($options['secure']);

		$before = $options['before'];
		$after = $options['after'];
		unset($options['before'], $options['after']);

		$isUrl = strpos($caption, '://') !== false;
		$isImage = preg_match('/\.(jpg|jpe|jpeg|gif|png|ico)$/', $caption);

		if ($isUrl || $isImage) {
			$unlockFields = array('x', 'y');
			if (isset($options['name'])) {
				$unlockFields = array(
					$options['name'] . '_x', $options['name'] . '_y'
				);
			}
			foreach ($unlockFields as $ignore) {
				$this->unlockField($ignore);
			}
		}

		if ($isUrl) {
			unset($options['type']);
			$tag = $this->Html->useTag('submitimage', $caption, $options);
		} elseif ($isImage) {
			unset($options['type']);
			if ($caption{0} !== '/') {
				$url = $this->webroot(Configure::read('App.imageBaseUrl') . $caption);
			} else {
				$url = $this->webroot(trim($caption, '/'));
			}
			$url = $this->assetTimestamp($url);
			$tag = $this->Html->useTag('submitimage', $url, $options);
		} else {
			$options['value'] = $caption;
			$tag = $this->Html->useTag('submit', $options);
		}
		$out = $before . $tag . $after;

		if (isset($divOptions)) {
			$tag = $divOptions['tag'];
			unset($divOptions['tag']);
			$out = $this->Html->tag($tag, $out, $divOptions);
		}
		return $out;
	}
/**
 * Creates an HTML link, but access the url using the method you specify (defaults to POST).
 * Requires javascript to be enabled in browser.
 *
 * This method creates a `<form>` element. So do not use this method inside an existing form.
 * Instead you should add a submit button using FormHelper::submit()
 *
 * ### Options:
 *
 * - `data` - Array with key/value to pass in input hidden
 * - `method` - Request method to use. Set to 'delete' to simulate HTTP/1.1 DELETE request. Defaults to 'post'.
 * - `confirm` - Can be used instead of $confirmMessage.
 * - Other options is the same of HtmlHelper::link() method.
 * - The option `onclick` will be replaced.
 * - `block` - For nested form. use View::fetch() output form.
 *
 * @param string $title The content to be wrapped by <a> tags.
 * @param string|array $url Cake-relative URL or array of URL parameters, or external URL (starts with http://)
 * @param array $options Array of HTML attributes.
 * @param bool|string $confirmMessage JavaScript confirmation message.
 * @return string An `<a />` element.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::postLink
 */
	public function postLink($title, $url = null, $options = array(), $confirmMessage = false) {
		$block = false;
		if (!empty($options['block'])) {
			$block = $options['block'];
			unset($options['block']);
		}

		$fields = $this->fields;
		$this->fields = array();

		$out = parent::postLink($title, $url, $options, $confirmMessage);

		$this->fields = $fields;

		if ($block) {
			$regex = '/<form.*?>.*?<\/form>/';
			if (preg_match($regex, $out, $match)) {
				$this->_View->append($block, $match[0]);
				$out = preg_replace($regex, '', $out);
			}
		}

		return $out;
	}

}
