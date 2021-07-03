<?php


/**
 * Handles the "hour" data for statistics
 * 
 * @author Andreas Lenhardt
 */
class stats_hour
{

    /**
     * 
     * 
     * @return array 
     * @throws InvalidArgumentException 
     * @throws rex_sql_exception 
     * @author Andreas Lenhardt
     */
    private function get_sql()
    {
        $sql = rex_sql::factory();
        $result = $sql->setQuery('SELECT hour, COUNT(hour) as "count" FROM ' . rex::getTable('pagestats_dump') . ' GROUP BY hour ORDER BY hour ASC');

        $data = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0, 11 => 0, 12 => 0, 13 => 0, 14 => 0, 15 => 0, 16 => 0, 17 => 0, 18 => 0, 19 => 0, 20 => 0, 21 => 0, 22 => 0, 23 => 0];

        foreach ($result as $row) {
            $data[$row->getValue('hour')] = $row->getValue('count');
        }

        return $data;
    }
    /**
     * 
     * 
     * @return (string|false)[] 
     * @throws InvalidArgumentException 
     * @throws rex_sql_exception 
     * @author Andreas Lenhardt
     */
    public function get_data()
    {
        $data = $this->get_sql();

        return [
            'labels' => json_encode(["00", "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23"]),
            'values' => json_encode(array_values($data)),
        ];
    }

    /**
     * 
     * 
     * @return string 
     * @throws InvalidArgumentException 
     * @throws rex_exception 
     * @author Andreas Lenhardt
     */
    public function get_list()
    {
        $list = rex_list::factory('SELECT hour, COUNT(hour) as "count" FROM ' . rex::getTable('pagestats_dump') . ' GROUP BY hour ORDER BY count DESC');
        $list->setColumnLabel('hour', 'Name');
        $list->setColumnLabel('count', 'Anzahl');
        $list->setColumnSortable('hour', $direction = 'asc');
        $list->setColumnSortable('count', $direction = 'asc');
        $list->setColumnFormat('hour', 'sprintf', '###hour### Uhr');

        return $list->get();
    }

    /**
     * 
     * 
     * @return array 
     * @throws InvalidArgumentException 
     * @throws rex_sql_exception 
     * @author Andreas Lenhardt
     */
    public function get_data_dashboard()
    {
        $data = $this->get_sql();

        return $data;
    }
}
