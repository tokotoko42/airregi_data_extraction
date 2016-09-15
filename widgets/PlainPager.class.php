<?php

class PlainPager extends CLinkPager
{
    public $header = false;
    public $footer = false;
    public $cssFile = false;

    public $firstPageLabel = '&laquo;';
    public $prevPageLabel = '&lsaquo;';
    public $nextPageLabel = '&rsaquo;';
    public $lastPageLabel = '&raquo;';

	/**
	 * Executes the widget.
	 * This overrides the parent implementation by displaying the generated page buttons.
	 */
	public function run()
	{
		$this->registerClientScript();
		$buttons=$this->createPageButtons();
		if(empty($buttons))
			return;
		echo $this->header;
		echo implode(' ', $buttons);
		echo $this->footer;
	}

	/**
	 * Creates a page button.
	 * You may override this method to customize the page buttons.
	 * @param string $label the text label for the button
	 * @param integer $page the page number
	 * @param string $class the CSS class for the page button. This could be 'page', 'first', 'last', 'next' or 'previous'.
	 * @param boolean $hidden whether this page button is visible
	 * @param boolean $selected whether this page button is selected
	 * @return string the generated button
	 */
	protected function createPageButton($label,$page,$class,$hidden,$selected)
	{
		if($hidden || $selected)
			$class.=' '.($hidden ? self::CSS_HIDDEN_PAGE : self::CSS_SELECTED_PAGE);
        if ($hidden) {
            return '';
        }
        if ($selected) {
            return $label;
        }
		return CHtml::link($label,$this->createPageUrl($page));
	}
}
