<?php
    require_once $_SESSION['rootDir'] . '../PHPExcel.php';
    require_once $_SESSION['rootDir'] . '../PHPExcel/IOFactory.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CFederalTaxCalculator
 *
 * @author Jeezy
 */
class CTaxCalculator {
    private $allowExemptions = true; // new tax plan has no exemptions
  
    private $totalWagePayment;
    private $numExemptions;
    private $numExemptionsState;
    private $amountSubjectToWithhold;

    private $federalTax;
    private $stateTax;

    private $withholdingAllowance;

    public function __construct($totalWagePayment, $exemptions = 0, $exemptionsState = 0) {
        $this->totalWagePayment = $totalWagePayment;
        $this->numExemptions = $exemptions;
        $this->numExemptionsState = $exemptionsState;
        
        //update one withholding allowance from excel spreadsheet
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objImport = $objReader->load($_SESSION['rootDir'] . 'files/Fed_Tax.xlsx');
        $this->withholdingAllowance = $objImport->getActiveSheet()->getCell('H' . 3)->getValue(); //update cell value if you want to change the allowance
        
        $this->SetAmountSubjectToWithhold($this->totalWagePayment, $this->numExemptions);
        
        $this->SetFederalTax($this->GetFederalWithholding($this->amountSubjectToWithhold));
        $this->SetStateTax($this->GetStateWithholding($this->totalWagePayment, $this->numExemptionsState));
    }
    public function __destruct() { ; }
    
    public function GetFederalTax() {
        return $this->federalTax;
    }
    private function SetFederalTax($to) {
        $this->federalTax = (float)$to;
    }
    public function GetStateTax() {
        return $this->stateTax;
    }
    private function SetStateTax($to) {
        $this->stateTax = (float)$to;
    }
    
    private function SetAmountSubjectToWithhold($totalWagePayment, $numExemptions) {
      if(!$this->allowExemptions) {
        $numExemptions = 0;
      }
      $totalAllowance = $this->withholdingAllowance*$numExemptions;
      $this->amountSubjectToWithhold = $totalWagePayment - $totalAllowance;
    }
    
    private function GetFederalWithholding($amountSubjectToWithhold) {
      //lookup values in excel table
      $objReader = PHPExcel_IOFactory::createReader('Excel2007');
      $objImport = $objReader->load($_SESSION['rootDir'] . 'files/Fed_Tax.xlsx');
      $row = array();

      $I = 3; //first row with actual values
      while ($objImport->getActiveSheet()->getCell('A' . $I)->getValue() != "") {
        $row['GT'] = (float)$objImport->getActiveSheet()->getCell('A' . $I)->getValue();
        $row['LT'] = (float)$objImport->getActiveSheet()->getCell('B' . $I)->getValue();
        if($row['LT'] == '--') { $row['LT'] = 9999999; } //the '--' is pasted directly from the federal tables, it means "no limit"
        //column C is a blank column, pasted from the federal tables.  then...
        $row['Withhold'] = (float)$objImport->getActiveSheet()->getCell('D' . $I)->getValue();
        //colum E just says 'plus', again this is pasted from the federal tables
        $row['PlusPercent'] = (float)$objImport->getActiveSheet()->getCell('F' . $I)->getValue();

        if(($amountSubjectToWithhold >= $row['GT']) && ($amountSubjectToWithhold < $row['LT'])) {
          //set basic withholding and excess percentage
          $excess = $amountSubjectToWithhold - $row['GT'];
          $totalWithholding = $excess*$row['PlusPercent']+$row['Withhold']; //calc total federal tax to be withheld

          return round($totalWithholding, 2);
        }

        ++$I;
      }
        
      return 0;
    }
    
    private function GetStateWithholding($totalWagePayment, $exemptions) {
        //lookup values in excel table
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objImport = $objReader->load($_SESSION['rootDir'] . 'files/State_Tax.xlsx');
        $row = array();

        $I = 4; //first row with actual values (we skip third row because of 0.00)
        $activeSheet = $objImport->getActiveSheet();
        while ($activeSheet->getCell('A' . $I)->getValue() != "") {
            $row['GT'] = (float)$objImport->getActiveSheet()->getCell('A' . $I)->getValue();
            $row['LT'] = (float)$objImport->getActiveSheet()->getCell('B' . $I)->getValue();
            
            if(($totalWagePayment >= $row['GT']) && ($totalWagePayment < $row['LT'])) {
                $exemptionColumn = 'C';
                
                switch($exemptions) {
                    case 0: $exemptionColumn = 'C'; break;
                    case 1: $exemptionColumn = 'D'; break;
                    case 2: $exemptionColumn = 'E'; break;
                    case 3: $exemptionColumn = 'F'; break;
                    case 4: $exemptionColumn = 'G'; break;
                    case 5: $exemptionColumn = 'H'; break;
                    case 6: $exemptionColumn = 'I'; break;
                    case 7: $exemptionColumn = 'J'; break;
                    case 8: $exemptionColumn = 'K'; break;
                    case 9: $exemptionColumn = 'L'; break;
                    case 10: $exemptionColumn = 'M'; break;
                    default: return 0;
                }
                
                $row['ExemptionState'] = (float)$objImport->getActiveSheet()->getCell($exemptionColumn . $I)->getValue();
                return $row['ExemptionState'];
            }
            
            ++$I;
	}
        
        return 0;
    }
}
