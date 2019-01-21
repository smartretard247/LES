<?php
include_once $_SESSION['rootDir'] . 'CTaxCalculator.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CDeductions
 *
 * @author Jeezy
 */
class CDeductions {
    private $federalTax;
    private $ficaSocSec;
    private $ficaMed;
    private $sgli;
    private $stateTax;
    private $afrh;
    private $sgliFam;
    private $midMonthPay;
    private $mmpOffset;

    public function __construct($basePay, $exemptions = 0, $exemptionsState = 0) {
        $this->federalTax = 0.0;
        $this->ficaSocSec = 0.0;
        $this->ficaMed = 0.0;
        $this->sgli = 0.0;
        $this->stateTax = 0.0;
        $this->afrh = 0.0;
        $this->sgliFam = 0.0;
        $this->midMonthPay = 0.0;
        $this->mmpOffset = 0.0;
        
        $this->SetSocialSecurity($basePay);
        $this->SetMedicare($basePay);
        
        $taxCalulator = new CTaxCalculator($basePay, $exemptions, $exemptionsState);
        
        $this->SetFederalTax($taxCalulator->GetFederalTax());
        $this->SetStateTax($taxCalulator->GetStateTax());
    }
    public function __destruct() { ; }
    
    public function GetFederalTax() {
        return $this->federalTax;
    }
    public function SetFederalTax($to) {
        $this->federalTax = (float)$to;
    }
    public function GetSocialSecurity() {
        return $_SESSION['Retired'] ? 0 : $this->ficaSocSec;
    }
    public function SetSocialSecurity($basePay) {
        $this->ficaSocSec = (float)number_format($basePay*0.062,2);
    }
    public function GetMedicare() {
        return $_SESSION['Retired'] ? 0 : $this->ficaMed;
    }
    public function SetMedicare($basePay) {
        $this->ficaMed = (float)number_format($basePay*0.0145,2);
    }
    public function GetSGLI() {
        return $this->sgli;
    }
    public function SetSGLI($to) {
        $this->sgli = (float)$to;
    }
    public function GetStateTax() {
        return $this->stateTax;
    }
    public function SetStateTax($to) {
        $this->stateTax = (float)$to;
    }
    public function GetAFRH() {
        return $this->afrh;
    }
    public function SetAFRH($to) {
        $this->afrh = (float)$to;
    }
    public function GetSGLIFam() {
        return $this->sgliFam;
    }
    public function SetSGLIFam($to) {
        $this->sgliFam = (float)$to;
    }
    public function GetMidMonthPay() {
        return $this->midMonthPay;
    }
    public function SetMidMonthPay($totalEntitlements, $totalAllotments, $daysInMonth) {
        $deductions = (float)($this->federalTax + $this->ficaSocSec + $this->ficaMed + $this->sgli + $this->stateTax + $this->afrh + $this->sgliFam);
        $mmp = (float)($totalEntitlements - $deductions - $totalAllotments)/$daysInMonth*15;
        $this->midMonthPay = $mmp + $this->mmpOffset;
    }
    public function OverrideMidMonthPay($override) {
      $this->midMonthPay = $override;
    }
    public function GetMMPOffset() {
        return $this->mmpOffset;
    }
    public function SetMMPOffset($to) {
        $this->mmpOffset = (float)$to;
    }
    
    public function GetTotalDeductions() {
        if($this->midMonthPay) {
            return $this->federalTax + $this->ficaSocSec + $this->ficaMed + $this->sgli + $this->stateTax + $this->afrh + $this->sgliFam + $this->midMonthPay;
        } else {
            return 0;
        }
    }
}
