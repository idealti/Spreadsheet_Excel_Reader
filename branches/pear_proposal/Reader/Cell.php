<?php

require_once 'Spreadsheet/Excel/Reader/Worksheet.php';

class Excel_Cell
{
    private $_type;

    private $_row_index;

    private $_col_index;

    private $_xf_index;


    /**
     * The raw value as stored in Excel
     */
    private $_value;

    public function __construct(Excel_Worksheet $worksheet, $row, $col, $xf, $value, $type = 'LABEL')
    {
        $this->worksheet  = $worksheet;
        $this->_row_index = $row;
        $this->_col_index = $col;
        $this->_xf_index  = $xf;
        $this->_value     = $value;
        $this->_type      = $type;
    }

    public function getValue() 
    {

        switch ($this->type) {


            case 'DATE':
            return $this->getDate();


            case 'NUMBER':
            
            break;

            case 'LABEL':
            default:

            return $this->_value;

        }

    }


    // TODO: figure out the rest
    private $date_format_map = array(
        'MMM' => 'M',
        'M'   => 'n',
        'D'   => 'j',
        'YY'  => 'y',
        'h'   => 'g',
        'mm'  => 'i',
        'ss'  => 's',
    );


    public function __toString()
    {
        return (string) $this->format();
    }

    public function format($format = null)
    {
        if (!is_null($format) && is_int($format)) {
            $format_index = $format;
        } else if (!is_null($format)) {
            // do something with the format str
        } else {
            $format_index = $this->worksheet->workbook->xf_records[$this->_xf_index]['format_index'];
        }

        $format_str   = $this->worksheet->workbook->format_records[$format_index];

        $type = $this->getType();
        if ($type === 'DATE') {
            return $this->getDateFormat($format_str);
        } else if ($type === 'NUMBER') {
            return $this->getNumberFormat($format_str);
        } else {
            return $this->_value;
        }
    }

    private function getDateFormat($format_str)
    {
        $date = $this->getDate();
        return $date->format(str_replace(array_keys($this->date_format_map),
                                         array_values($this->date_format_map),
                                         $format_str));
    }


    // todo handle money format
    private function getNumberFormat($format_str)
    {
        $is_formatted = strstr($format_str, '#,##') !== false;
        $matches = array();
        if (preg_match('/0\.(0)+/', $format_str, $matches)) {
            $precision = strlen($matches[1]);
        } else {
            $precision = 0;
        }

        $is_percentage = strstr($format_str, '%') !== false;

        $value = $this->_value;

        if ($is_percentage) {
            $value *= 100;
        }

        if ($precision > 0) {
            $value = round($value, $precision);
        }

        if ($is_formatted) {
            $value = number_format($value);
        }

        if ($is_percentage) {
            $value .= '%';
        }

        return $value;
    }

    public function getDate()
    {
        $utc_offset = $this->worksheet->workbook->datemode === 1 ?
                      Spreadsheet_Excel_Reader::UTCOFFSETDAYS1904 :
                      Spreadsheet_Excel_Reader::UTCOFFSETDAYS;
        $utc_days = $this->_value - $utc_offset;
        $utc_secs = $utc_days * Spreadsheet_Excel_Reader::SECONDSINADAY;
        return new DateTime(date('r', $utc_secs));
    }

    public function getType()
    {
        $format_index = $this->worksheet->workbook->xf_records[$this->_xf_index]['format_index'];
        $format_str   = $this->worksheet->workbook->format_records[$format_index];

        // need to improve this check
        if ($format_index >= 14 && $format_index <= 22 ||
            $format_index >= Excel_Workbook::USER_DEFINED_FORMATS && preg_match('/[dmY]/', $format_str)) {

            return 'DATE';

        // need to improve this check
        } else if ($format_index >= 5 && $format_index <= 8) {

            return 'MONEY';

        } else if ($format_index == 0) {

            return 'LABEL';

        } else { 

            return 'NUMBER';
        }

/*
        if (['type'] == 'date') {
            $this->curformat = $this->workbook->xf_records[$_xf_index]['format'];
            $this->rectype = 'date';
            return true;

        } else {
            if ($this->workbook->xf_records[$_xf_index]['type'] == 'number') {
                $this->curformat = $this->workbook->xf_records[$_xf_index]['format'];
                $this->rectype = 'number';
                if (($_xf_index == 0x9) || ($_xf_index == 0xa)){
                    $this->multiplier = 100;
                }
            }else{
                $this->curformat = Spreadsheet_Excel_Reader::DEF_NUM_FORMAT;
                $this->rectype = 'unknown';
            }
            return false;
        
        }
        */
    }
}

?>
