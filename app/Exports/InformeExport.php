<?php

namespace App\Exports;

use Illuminate\Support\Collection;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class InformeExport implements FromArray, WithHeadings, WithTitle
{
    protected $data;
    protected $cabecera;
    protected $title;

    public function __construct($data, $cabecera, $title){
        //	converts data to array
		$temp_data = [];
		foreach ( $data as $item )
		{
			//	converts to array
			$item = (array)$item;
			//	if has to format the fields
			if ( !empty($fields_format) && is_array($fields_format) )
			{
				//	foreach field
				foreach ( $fields_format as $format_item )
				{
					//	if its decimal
					if ( isset($format_item['field']) && isset($format_item['format']) && $format_item['format'] == 'decimal' && isset($item[$format_item['field']]) )
					{
						//	replace and cast decimal
						$item[$format_item['field']] = str_replace('.', '', $item[$format_item['field']]);
						$item[$format_item['field']] = str_replace(',', '.', $item[$format_item['field']]);
						$item[$format_item['field']] = floatval($item[$format_item['field']]);
					}
				}
			}
			//	sets item
			$temp_data[] = $item;
		}
		//	sets array
		$this->data = $temp_data;
        $this->cabecera = $cabecera;
        $this->title = $title;
    }
    
    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->cabecera;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->title;
    }

}
