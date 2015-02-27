<?php

/**
 * php class to support rtl direction in any css file
 *
 * PHP version 5
 *
 * @category   Utility
 * @package    UnitOneICT\RTLer
 * @author     Mohammed S Shurrab <m.sh@unitone.ps>
 */
use Sabberworm\CSS;

function __autoload($class) {
    require $class . '.php';
}

class RTLer {

    /**
     * @var string
     */
    var $origenl_css;

    /**
     * @var CSS\Parser
     */
    var $parser;

    /**
     * @var CSS\CSSList\Document
     */
    var $document;

    /**
     * this array contain any css rule that can effect the style direction
     * @var array 
     */
    var $dir_rules = array(
        "direction",
        "text-align",
        "float",
        "left",
        "right",
        "background",
        "padding",
        "margin",
        "border-radius",
        "border",
        "box-shadow"
		// TODO: complete the list
    );

    /**
     * 
     * @param string $file css file path
     */
    function __construct($file = "style.css") {
        $this->origenl_css = file_get_contents($file);
        $this->parser = new CSS\Parser($this->origenl_css, CSS\Settings::create()->withMultibyteSupport(false));
        $this->document = $this->parser->parse();
    }

    /**
     * render the rtled css code, by defualt it will return a string
     * @param bool $save_to_file if true save the code in a rtl.css file
     */
    public function render($save_to_file = false) {
        return $this->document->render(CSS\OutputFormat::createPretty());
    }

    /**
     * Loop over the rules and remove any neutral one
     * If the rule set become empty remove it also!
     */
    public function remove_direction_neutral_rules() {
        foreach ($this->document->getAllRuleSets() as $rule_sets) {
            /* @var $rule_sets CSS\RuleSet\RuleSet */
            foreach ($rule_sets->getRules() as $rule) {
                /* @var $rule CSS\Rule\Rule */
                $rule_root = explode("-", $rule->getRule())[0];
                if (!in_array($rule->getRule(), $this->dir_rules) && !in_array($rule_root, $this->dir_rules)) {
                    $rule_sets->removeRule($rule);
                }
            }
            if (empty($rule_sets->getRules())) {
                $this->document->remove($rule_sets);
            }
        }
    }
    
    public function remove_empty_rulelist() {
        foreach ($this->document->getContents() as $item) {
            if ($item instanceof CSS\CSSList\AtRuleBlockList) {
                /* @var $item CSS\CSSList\AtRuleBlockList */
                foreach ($item->getContents() as $rule_sets) {
                    if (empty($rule_sets->getRules())) {
                        $item->remove($rule_sets);
                    }
                }
                if (empty($item->getContents())) {
                    $this->document->remove($item);
                }
            }
        }
    }

    function rtl() {
        $this->remove_direction_neutral_rules();

        foreach ($this->document->getAllRuleSets() as $rule_sets) {
            /* @var $rule_sets CSS\RuleSet\RuleSet */
            foreach ($rule_sets->getRules() as $rule) {
                /* @var $rule CSS\Rule\Rule */
                $neutral = TRUE;

                /* @var $value CSS\Value\RuleValueList */
                $value = $rule->getValue();
                if ($value instanceof CSS\Value\RuleValueList) {
                    if ($rule->getRule() == "background" || $rule->getRule() == "background-position") {
                        $neutral = $this->rtl_background($value);
                    } elseif ($rule->getRule() == "box-shadow") {
                        $neutral = $this->rtl_box_shadow($value);
                    } elseif ($rule->getRule() == "border-radius") {
                        $neutral = $this->rtl_border_radius_components($value);
                    } else {
                        $components = $value->getListComponents();
                        if (count($components) == 4) {
                            $neutral = $this->rtl_four_components($value);
                        }
                    }
                }

                /**
                 * Replace ltr, left to rtl, right both in rule and value
                 * it must not be replaced in the selectors themeselves
                 */
                if (is_int(strpos($rule->getRule(), "left")) || is_int(strpos($rule->getRule(), "right"))) {
                    $neutral = FALSE;
                    $swaped_rule = str_replace(array("left", "right", "swap"), array("swap", "left", "right"), $rule->getRule());
                    $current_rules = $rule_sets->getRules($swaped_rule);
                    $reset_rule = (empty($current_rules)) ? clone($rule) : FALSE;
                    $rule->setRule($swaped_rule);
                    // reset the defualt rule to auto if not exists already
                    if ($reset_rule) {
						$reset_value = "initial";
						if(is_int(strpos($rule->getRule(), "border-")))
							$reset_value = "transparent";
                        $reset_rule->setValue($reset_value);
                        $rule_sets->addRule($reset_rule);
                    }
                }
                if (is_int(strpos($rule->getValue(), "left")) || is_int(strpos($rule->getValue(), "right"))) {
                    $neutral = FALSE;
                    $rule->setValue(str_replace(array("left", "right", "swap"), array("swap", "left", "right"), $rule->getValue()));
                }
                if (is_int(strpos($rule->getValue(), "left")) || is_int(strpos($rule->getValue(), "right"))) {
                    $neutral = FALSE;
                    $rule->setValue(str_replace(array("ltr", "rtl", "swap"), array("swap", "ltr", "rtl"), $rule->getValue()));
                }

                if ($neutral) {
                    $rule_sets->removeRule($rule);
                }
            }

            if (empty($rule_sets->getRules())) {
                $this->document->remove($rule_sets);
            }
        }

        $this->remove_empty_rulelist();
    }

    /**
     * 
     * @param CSS\Value\Size[] $components
     */
    function rtl_four_components($value) {
		$components = $value->getListComponents();
		$right = $components[1];
		$components[1] = $components[3];
        $components[3] = $right;
		$value->setListComponents($components);
        return false;
    }

    /**
     * rtl background position-x and gradient
     * @param CSS\Value\RuleValueList $value
     */
    public function rtl_background($value) {
        // if background poition-x is % or px rtl them
        /* @var $components CSS\Value\Size[] */
        $components = $value->getListComponents();
		foreach ($components as $key => $component) {
            if (is_string($component) && in_array($component, array('left', 'right'))) {
                // don't do any thing, this will be swaped later
                return true;
            } elseif ($component instanceof CSS\Value\Size) {
                if ($component->getUnit() == "%") {
                    $component->setSize(100 - $component->getSize());
                    return false;
                } elseif ($component->getUnit() == "px") {
					$rtl_components = array();
					foreach ($components as $ikey => $icomponent) {
						if($key == $ikey)
							$rtl_components [] = "left"; // it will be swaped to right later
						$rtl_components [] = $icomponent;
					}
					$value->setListComponents($rtl_components);
					return false;
                }
            }
        }
    }

    /**
     * 
     * @param CSS\Value\RuleValueList $value
     */
    public function rtl_box_shadow($value) {
        // skip the optional color and multibly the first size value * -1
        $components = $value->getListComponents();
        $horizontal_length;
        if (!($components[0] instanceof CSS\Value\Size)) {
            $horizontal_length = $components[1];
        } else {
            $horizontal_length = $components[0];
        }
        $horizontal_length->setSize($horizontal_length->getSize() * -1);
        return false;
    }

    /**
     * 
     * @param CSS\Value\RuleValueList $value
     */
    public function rtl_border_radius_components($value) {
        // border-radius: 25px 10px => 10px 25px
        /*  @var $components CSS\Value\Size[] */
        $components = $value->getListComponents();
        if (count($components) == 2) {
            $top_left = $components[1]->getSize();
            $top_left_unit = $components[1]->getUnit();
            $components[1]->setSize($components[0]->getSize());
            $components[1]->setUnit($components[0]->getUnit());
            $components[0]->setSize($top_left);
            $components[0]->setUnit($top_left_unit);
            return false;
        } else if (count($components) == 3) {
            // border-radius: 25px 10px 15px => 10px 25px 10px 15px;
            // swap 1st and 2nd components
            $top_left = $components[1]->getSize();
            $top_left_unit = $components[1]->getUnit();
            $components[1]->setSize($components[0]->getSize());
            $components[1]->setUnit($components[0]->getUnit());
            $components[0]->setSize($top_left);
            $components[0]->setUnit($top_left_unit);
            // copy 3rd to 4th components
            $value->addListComponent(clone($components[2]));
            // copy the 1st components to the 3rd
            $components[2]->setSize($components[0]->getSize());
            $components[2]->setUnit($components[0]->getUnit());
            return false;
        } else if (count($components) == 4) {
            // border-radius: 25px 10px 15px 8px => 10px 25px 8px 15px;
            // swap 1st and 2nd components
            $top_left = $components[1]->getSize();
            $top_left_unit = $components[1]->getUnit();
            $components[1]->setSize($components[0]->getSize());
            $components[1]->setUnit($components[0]->getUnit());
            $components[0]->setSize($top_left);
            $components[0]->setUnit($top_left_unit);

            // swap 3rd and 4th components
            $buttom_right = $components[3]->getSize();
            $buttom_right_unit = $components[3]->getUnit();
            $components[3]->setSize($components[2]->getSize());
            $components[3]->setUnit($components[2]->getUnit());
            $components[2]->setSize($buttom_right);
            $components[2]->setUnit($buttom_right_unit);

            return false;
        } else {
            return true;
        }
    }

}
