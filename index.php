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
            // TODO: complete the list
    );

    /**
     * 
     * @param string $file css file path
     */
    function __construct($file = "style.css") {
        $this->origenl_css = file_get_contents($file);
        $this->parser = new CSS\Parser($this->origenl_css);
        $this->document = $this->parser->parse();
    }

    function rtl() {
        $this->remove_direction_neutral_rules();

        // TODO: loop over the rule and rtl it
        foreach ($this->document->getAllRuleSets() as $rule_sets) {
            /* @var $rule_sets CSS\RuleSet\RuleSet */
            foreach ($rule_sets->getRules() as $rule) {
                /* @var $rule CSS\Rule\Rule */
                $neutral = TRUE;

                $value = $rule->getValue();
                if ($value instanceof CSS\Value\RuleValueList) {
                    /* @var $value CSS\Value\RuleValueList */
                    $components = $value->getListComponents();
                    if (count($components) == 4 && $components[1] instanceof CSS\Value\Size && $components[3] instanceof CSS\Value\Size) {
                        /* @var $components CSS\Value\Size[] */
                        $right_size = $components[1]->getSize();
                        $right_unit = $components[1]->getUnit();
                        $components[1]->setSize($components[3]->getSize());
                        $components[1]->setUnit($components[3]->getUnit());
                        $components[1]->setSize($right_size);
                        $components[1]->setUnit($right_unit);
                        $neutral = FALSE;
                    }
                }
                /**
                 * Replace ltr, left to rtl, right both in rule and value
                 * it must not be replaced in the selectors themeselves
                 */
                $rule->setRule(str_replace(array("left", "right", "swap"), array("swap", "left", "right"), $rule->getRule()));
                $rule->setValue(str_replace(array("left", "right", "swap"), array("swap", "left", "right"), $rule->getValue()));
                $rule->setValue(str_replace(array("ltr", "rtl", "swap"), array("swap", "ltr", "rtl"), $rule->getValue()));
                
                if ($neutral) {
                    $rule_sets->removeRule($rule);
                }
            }
            if (empty($rule_sets->getRules())) {
                $this->document->remove($rule_sets);
            }
        }
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
                $rule_root = explode("-",$rule->getRule())[0];
                if (!in_array($rule_root, $this->dir_rules)) {
                    $rule_sets->removeRule($rule);
                }
            }
            if (empty($rule_sets->getRules())) {
                $this->document->remove($rule_sets);
            }
        }
    }

    /**
     * render the rtled css code, by defualt it will return a string
     * @param bool $save_to_file if true save the code in a rtl.css file
     */
    public function render($save_to_file = false) {
        return $this->document->render();
    }

}

$rtler = new RTLer();
$rtler->rtl();
echo $rtler->render();