<?php
/**
 *
 */
class VehicleField extends FormField {

	private $_limit = 3;

	private $_data = array();

	/**
	 *
	 */
	public function __construct($name, $title = null, $limit = 1, $value = "", $form = null) {
		// we have labels for the subfields
		$title = false;

		$this->setLimit($limit);
		parent::__construct($name, $title, null, $form);
	}

	/**
	 *
	 */
	public function setData($data = array()) {
		if(!isset($data[$this->name])) {
			return null;
		}

		// remove not in indexes
		foreach($data[$this->name] as $index => $value) {
			if(!is_numeric($index)) {
				unset($data[$this->name][$index]);
			}
		}
		
		$this->_data = $data[$this->name];
	}

	/**
	 *
	 */
	private function createFields() {
		$index = 0;
		$children = array();

		do {
			$values = array(
				'Trailer1' => '',
				'Trailer2' => '',
				'HPMVPermits' => '1',
				'HPMVAxleType' => '0',
			);

			if(isset($this->_data[$index])) {
				$values = $this->_data[$index];
			}

			$trailer1 = new TextField(
				"{$this->name}[$index][Trailer1]", 
				"Trailer 1 registration no (Max 6 characters) <span class='required-identifier'>(required)</span>",
				$values['Trailer1'], 6
			);

			$trailer1->addExtraClass('requiredField')
					->setAttribute('required', 'required');

			$file =	new FileField(
				"{$this->name}[$index][MaxFile]",
				'Attach 50MAX combination vehicle attribute check sheet <span class="required-identifier">(required)</span>'
			);

			$file->addExtraClass('requiredField')
				->setAttribute('required', 'required')
				->setTemplate('UserFormsFileField');

			$axletype = new OptionsetField(
							"{$this->name}[$index][HPMVAxleType]",
							'What is your axle type?',
							array(
								'0' => 'Single',
								'1' => 'Single large tyres',
								'2' => 'Mega tyres',
								'3' => 'Twin-tyred'
							),
							$values['HPMVAxleType']
						);

			$axletype->addExtraClass('requiredField')
					->setAttribute('required', 'required');

			// saved as an array so the group of vehicles added can be parsed better later
			$children[] = new FieldList(
				new HeaderField(
					"{$this->name}[$index][Header]",
					"Vehicle combination " . ($index + 1),
					4
				),
				$trailer1,
				new TextField(
					"{$this->name}[$index][Trailer2]",
					"Trailer 2 (B train only) registration no (Max 6 characters)",
					$values['Trailer2'], 6
				),
				new OptionsetField(
					"{$this->name}[$index][HPMVPermits]",
					'Over Length HPMV Permits',
					array(
						'1' => 'I already have a HPMV permit to exceed length limits',
						'0' => 'I do not have a HPMV permit to exceed length limits'
					),
					$values['HPMVPermits']
				),
				$file,
				$axletype
			);

			$index++;
		} while(isset($this->_data[$index]));
		
		$this->children = $children;
	}

	/**
	 *
	 */
	public function Field($properties = array()) {
		$this->createFields();
		$content = '<div class="vehicle__holder theme--paper layout section-s" data-limit="' . $this->_limit . '">';
		
		// get last element to add active class
		$end = count($this->children);
		$count = 1;
		$session = Session::get('FormInfo.Form_Form.vehicleField');

		foreach($this->children as $fieldlist) {
			if($count === $end) {
				$content .= '<div class="vehicleWrap active">';
			} else {
				$content .= '<div class="vehicleWrap">';
			}

			// Adding file error to the vehicle field
			if(isset($session['pos']) && $session['pos'] + 1 == $count) {
				foreach($fieldlist as $field) {
					if($field->Name == "{$this->name}[" . $session['pos'] . "][MaxFile]") {
						$field->setError($session['message'], 'bad');
					}
				}
			}

			// Adding Axle type error
			if(isset($session['pos_axle']) && $session['pos_axle'] + 1 == $count) {
				foreach($fieldlist as $field) {
					if($field->Name == "{$this->name}[" . $session['pos_axle'] . "][HPMVAxleType]") {
						$field->setError($session['message_axle'], 'bad');
					}
				}
			}

			foreach($fieldlist as $field) {
				$content .= $field->FieldHolder();
			}

			$content .= '</div>';
			$count++;
		}

		Session::clear("FormInfo.Form_Form.vehicleField");

		$content .= '<a class="addVehicle btn btn--secondary btn--block-mobile" href="javascript:void(0)">Add another Vehicle combination</a>';
		$content .= '<a class="removeVehicle btn btn--secondary btn--block-mobile" href="javascript:void(0)">Remove this Vehicle combination</a>';
		$content .= '</div>';

		return $content;
	}

	/**
	 * Overwriting validation to catch file issues for this field
	 */
	public function validate($validator) {

		/* File upload */
		if(isset($_FILES[$this->Name])) {

			foreach($_FILES[$this->Name]['error'] as $i => $error) {
				if($error['MaxFile'] != 0) {
					$message = 'File is not a valid upload';

					// {@link http://www.php.net/manual/en/features.file-upload.errors.php}
					if($error['MaxFile'] == 1 || $error['MaxFile'] == 2) {
						$message = 'File is not a valid upload, it exceedes the maximum file size.';
					}

					Session::set("FormInfo.Form_Form.vehicleField", array(
						'pos' => $i,
						'message' => $message
					));
					return false;
				}
			}
		}

		foreach($this->value as $key => $value){
			if($value['HPMVAxleType'] != 3){
				$validator->validationError(
					$this->name.'['.$key.'][HPMVAxleType]', "Axle type should be twin-tyred", "validation", false
				);

				$message = 'Axle type should be twin-tyred !! ';
					Session::set("FormInfo.Form_Form.vehicleField", array(
							'pos_axle' => 0,
							'message_axle' => $message
						));

				return false;
			}
		}
		
		return true;
	}

	/**
	 * @param int $limit set of vehicle blocks that are going to be displayed to the user
	 * @return VehicleField instance of element
	 */
	public function setLimit($limit = 3) {
		if(!is_int($limit)) {
			$limit = 3;
		}
		$this->_limit = $limit;

		return $this;
	}
}
