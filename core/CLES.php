<?php
    include_once $_SESSION['rootDir'] . 'CEntitlements.php';
    include_once $_SESSION['rootDir'] . 'CDeductions.php';
    include_once $_SESSION['rootDir'] . 'CAllotments.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CLES
 *
 * @author Jeezy
 */
class CLES {
    private $entitlements;
    private $deductions;
    private $allotments;
    
    public function __construct($rank, $tis, $exemptions = 0, $exemptionsState = 0) {
        $this->entitlements = new CEntitlements($rank, $tis);
        $this->deductions = new CDeductions($this->entitlements->GetBasePay(), $exemptions, $exemptionsState);
        $this->allotments = new CAllotments();
    }
    public function __destruct() { ; }
    
    public function GetEntitlements() {
        return $this->entitlements;
    }
    public function GetDeductions() {
        return $this->deductions;
    }
    public function GetAllotments() {
        return $this->allotments;
    }
    
    public function GetNetAmount($offset = 0) {
        return $this->entitlements->GetTotalEntitlements($offset) - $this->deductions->GetTotalDeductions() - $this->allotments->GetTotalAllotments();
    }
    
    public function GetEOMPay($crFwd, $offset = 0) {
        return $this->entitlements->GetTotalEntitlements($offset) - $this->deductions->GetTotalDeductions() - $this->allotments->GetTotalAllotments() - $crFwd;
    }
}
