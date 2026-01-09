<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;

class ExportService
{
    /**
     * 导出为CSV格式
     */
    public function exportToCsv(Collection $data, array $headers, array $fields, string $filename): string
    {
        $output = fopen('php://temp', 'r+');
        
        // 添加BOM以支持Excel正确显示中文
        fwrite($output, "\xEF\xBB\xBF");
        
        // 写入表头
        fputcsv($output, $headers);
        
        // 写入数据
        foreach ($data as $row) {
            $rowData = [];
            foreach ($fields as $field) {
                $value = $this->getNestedValue($row, $field);
                $rowData[] = $value;
            }
            fputcsv($output, $rowData);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * 获取嵌套属性值
     */
    private function getNestedValue($object, string $field): string
    {
        $keys = explode('.', $field);
        $value = $object;
        
        foreach ($keys as $key) {
            if (is_array($value)) {
                $value = $value[$key] ?? null;
            } elseif (is_object($value)) {
                $value = $value->{$key} ?? null;
            } else {
                $value = null;
                break;
            }
        }
        
        if ($value === null) {
            return '';
        }
        
        if (is_bool($value)) {
            return $value ? '是' : '否';
        }
        
        return (string) $value;
    }
}
