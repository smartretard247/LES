<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CEntitlements
 *
 * @author Jeezy
 */
class CEntitlements {
    private $basePay;
    private $bas;
    private $bah;
    private $hdp;
    private $oha;
    private $colaDailyRate;
    private $colaDailyRateM;
    private $famSep;
    private $clothing;
    private $clothingMon = 10;
    
    public function __construct($rank, $tis) {
        $this->InitializeFromDB($rank, $tis);
        
    }
    public function __destruct() { ; }
    
    public function GetClothing($mon) {
      switch($mon) {
        case $this->clothingMon: return $this->clothing;
      }
      
      return 0;
    }
    public function SetClothing($to) {
        $this->clothing = (float)$to;
    }
    
    public function GetBasePay() { return $this->basePay; }
    public function SetBasePay($to) {
        $this->basePay = (float)$to;
    }
    public function GetBAS() { return $this->bas; }
    public function SetBAS($to) {
        $this->bas = (float)$to;
    }
    public function GetBAH() { return $this->bah; }
    public function SetBAH($to) {
        $this->bah = (float)$to;
    }
    public function GetHDP() { return $this->hdp; }
    public function SetHDP($to) {
        $this->hdp = (float)$to;
    }
    public function GetOHA() { return $this->oha; }
    public function SetOHA($to) {
        $this->oha = (float)$to;
    }
    
    public function GetCOLADailyRate($forMM = false) {
      if($forMM) {
        return $this->colaDailyRateM;
      } else {
        return $this->colaDailyRate;
      }
    }
    public function SetCOLADailyRate($to, $forMM = false) {
      if($forMM) {
        $this->colaDailyRateM = (float)$to;
      } else {
        $this->colaDailyRate = (float)$to;
      }
    }
    public function GetCOLA($daysInMonth) { 
      $mmDays = $daysInMonth - 15;
      $mmCola = $mmDays*$this->colaDailyRateM;
      $firtHalfCola = 15*$this->colaDailyRate;
      return $_SESSION['Retired'] ? 0 : round($firtHalfCola+$mmCola,2); 
    }
    
    public function GetFamilySeparation() {
        return $this->famSep;
    }
    public function SetFamilySeparation($to) {
        $this->famSep = (float)$to;
    }
    
    public function GetDaysInNextMonth($offset = 0) {
      $nextMonth = date_create_from_format('Y-m', date('Y-m'));
      date_add($nextMonth, date_interval_create_from_date_string("$offset month"));
      return $nextMonth->format('t');
    }
    
    public function GetMonthWithOffset($offset = 0) {
      $offsetMonth = date_create_from_format('Y-m', date('Y-m'));
      date_add($offsetMonth, date_interval_create_from_date_string("$offset month"));
      return $offsetMonth->format('n');
    }

    public function GetTotalEntitlements($offset = 0) {
      
      return (float)($this->basePay + $this->bas + $this->bah + $this->hdp + $this->oha + $this->GetClothing($this->GetMonthWithOffset($offset)) + $this->GetCOLA($this->GetDaysInNextMonth($offset)));
    }
    
    private function InitializeFromDB($rank, $tis) {
        require_once $_SESSION['rootDir'] . '../database.php';
        global $db;
        
        $this->basePay = 0.0;
        $this->bas = 0.0;
        $this->bah = 0.0;
        $this->hdp = 0.0;
        $this->oha = 0.0;
        $this->colaDailyRate = 0.0;
        $this->colaDailyRateM = 0.0;
        $this->famSep = 0.0;
        $this->clothing = 0.0;
        
        $commision = explode('-', $rank); //E-6
        $this->SetBAS($_SESSION['Retired'] ? 0 : $this->FindBAS($commision[0])); //first index contains the E, W, or O
        $this->SetBasePay($this->FindBasePay($rank, $tis));
    }
    
    private function FindBAS($commision) {
        //lookup values in excel table
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');

        $objImport = $objReader->load($_SESSION['rootDir'] . 'files/BAS.xlsx');
	
        $row = 2; //first row with actual values
        while ($objImport->getActiveSheet()->getCell('A' . $row)->getValue() != "") {
            if($objImport->getActiveSheet()->getCell('A' . $row)->getValue() == $commision) {
                break;
            }

            ++$row;
        }
        
        return (float)$objImport->getActiveSheet()->getCell('B' . $row)->getValue();
    }
    
    private function FindBasePay($rank, $tisNumber) {
      if($rank == "O-10" || $rank == "Retired") { $rank = "O-10 1"; }
      else if($rank == "O-9") { $rank = "O-9 1"; }
      else if($rank == "O-8") { $rank = "O-8 1"; }
      else if($rank == "O-7") { $rank = "O-7 1"; }
      else if($rank == "O-6") { $rank = "O-6 2"; }
      else if($rank == "E-9") { $rank = "E-9 4"; }
      
      switch ($tisNumber) {
        case 1: $tis = "2 or less"; break;
        case 2: $tis = "Over 2"; break;
        case 3: $tis = "Over 3"; break;
        case 4: $tis = "Over 4"; break;
        case 6: $tis = "Over 6"; break;
        case 8: $tis = "Over 8"; break;
        case 10: $tis = "Over 10"; break;
        case 12: $tis = "Over 12"; break;
        case 14: $tis = "Over 14"; break;
        case 16: $tis = "Over 16"; break;
        case 18: $tis = "Over 18"; break;
        case 20: $tis = "Over 20"; break;
        case 22: $tis = "Over 22"; break;
        case 24: $tis = "Over 24"; break;
        case 26: $tis = "Over 26"; break;
        case 28: $tis = "Over 28"; break;
        case 30: $tis = "Over 30"; break;
        case 32: $tis = "Over 32"; break;
        case 34: $tis = "Over 34"; break;
        case 36: $tis = "Over 36"; break;
        case 38: $tis = "Over 38"; break;
        case 40: $tis = "Over 40"; break;
      }
      
      
        //lookup values in excel table
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objImport = $objReader->load($_SESSION['rootDir'] . 'files/Base_Pay.xlsx');
        $row = 3; //first row with actual values
        while ($objImport->getActiveSheet()->getCell('A' . $row)->getValue() != "") {
            if($objImport->getActiveSheet()->getCell('A' . $row)->getValue() == $rank) {
                break;
            }
            
            ++$row;
        }
        
        $col = 'B';
        while ($objImport->getActiveSheet()->getCell($col . 2)->getValue() != "") {
            if($objImport->getActiveSheet()->getCell($col . 2)->getValue() == $tis) {
                break;
            }

            ++$col;
        }
        
        return (float)$objImport->getActiveSheet()->getCell($col . $row)->getValue();
    }
}
