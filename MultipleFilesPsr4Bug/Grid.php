<?php declare (strict_types=1);


final class Grid
{
    private $filters = [];

    protected function getFilterQuery()
    {
        if ($this->filters) {
            $f = [];

            foreach ($this->filters as $column => $value) {
                $escValue = strtoupper($value) === 'IS NULL' || strtoupper($value) === 'IS NOT NULL'
                    ? $value
                    : '';

                $f[] = $column . ' ' . $escValue;
            }

            return implode(' AND ', $f);
        }

        return false;
    }
}
