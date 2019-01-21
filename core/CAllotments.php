<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CAllotments
 *
 * @author Jeezy
 */
class CAllotments {
    private $allotments;
    
    public function __construct() {
        $this->allotments = array();
    }
    public function __destruct() { ; }
    
    public function AddAllotment($nameOfAccount, $amount) {
        $this->allotments[] = array('Name' => $nameOfAccount, 'Amount' => (float)$amount);
    }
    
    public function GetSlotName($slot) {
        return $this->allotments[$slot]['Name'];
    }
    public function GetSlotAmount($slot) {
        return $this->allotments[$slot]['Amount'];
    }
    
    public function GetNumSlots() {
        return sizeof($this->allotments);
    }
    
    public function GetTotalAllotments() {
        $total = 0.0;
        
        foreach($this->allotments as $row) {
            $total += $row['Amount'];
        }
        
        return $total;
    }
}
