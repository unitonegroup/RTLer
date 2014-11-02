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

namespace UnitOneICT\RTLer;

use Sabberworm\CSS;

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
        "border-radius-top",
        "border-radius-button",
        "border",
        // TODO: complete the list
    );
    
    /**
     * 
     * @param string $file css file path
     */
    function __construct($file = "style.css"){
        $this->origenl_css = file_get_contents($file);
        $this->parser = new CSS\Parser($this->origenl_css);
        $this->document = $this->parser->parse();
    }
    
    function rtl(){
        $this->remove_direction_neutral_rules();
        
        // TODO: loop over the rule and rtl it
    }

    /**
     * Loop over the rules and remove any neutral one
     * If the rule set become empty remove it also!
     */
    public function remove_direction_neutral_rules() {
        
    }
    
    /**
     * render the rtled css code, by defualt it will return a string
     * @param bool $save_to_file if true save the code in a rtl.css file
     */
    public function render($save_to_file = false){
        
    }
}