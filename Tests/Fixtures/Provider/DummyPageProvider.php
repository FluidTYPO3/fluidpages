<?php
namespace FluidTYPO3\Fluidpages\Tests\Fixtures\Provider;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Provider\SubPageProvider;
use FluidTYPO3\Flux\Form;

/**
 * Class DummyPageProvider
 */
class DummyPageProvider extends SubPageProvider {

	/**
	 * @var array
	 */
	protected $values = array();

	/**
	 * @param array $row
	 * @param string $table
	 * @param string $field
	 * @param string $extensionKey
	 * @return boolean
	 */
	public function trigger(array $row, $table, $field, $extensionKey = NULL) {
		return TRUE;
	}

	/**
	 * @param array $values
	 * @return void
	 */
	public function setFlexFormValues(array $values) {
		$this->values = $values;
	}

	/**
	 * @param array $row
	 * @return array()
	 */
	public function getFlexFormValues(array $row) {
		return array();
	}

	/**
	 * @param array $row
	 * @return Form
	 */
	public function getForm(array $row) {
		return $this->form;
	}

}
