<?php

namespace Spatie\SimpleExcel;

use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\ReaderInterface;
use Illuminate\Support\LazyCollection;

class SimpleExcelReader
{
    /** @var \Box\Spout\Reader\ReaderInterface */
    private $reader;

    /** @var \Box\Spout\Reader\IteratorInterface */
    private $rowIterator;

    private $processHeader = true;

    public static function create(string $file)
    {
        return new static($file);
    }

    public function __construct(string $path)
    {
        $this->reader = ReaderEntityFactory::createReaderFromFile($path);

        $this->reader->open($path);
    }

    public function noHeader()
    {
        $this->processHeader = false;

        return $this;
    }

    public function getReader(): ReaderInterface
    {
        return $this->getReader();
    }

    public function getRows(): LazyCollection
    {
        $sheet = $this->reader->getSheetIterator()->current();

        $this->rowIterator = $sheet->getRowIterator();

        $this->rowIterator->rewind();

        /** @var \Box\Spout\Common\Entity\Row $firstRow */
        $firstRow = $this->rowIterator->current();

        if (is_null($firstRow)) {
            $this->noHeader();
        }

        if ($this->processHeader) {
            $this->headers = $firstRow->toArray();
            $this->rowIterator->next();
        }

        return LazyCollection::make(function () {
            while ($this->rowIterator->valid()) {
                $row = $this->rowIterator->current();

                yield $this->getValueFromRow($row);

                $this->rowIterator->next();
            }
        });
    }

    protected function getValueFromRow(Row $row): array
    {
        if (!$this->processHeader) {
            return $row->toArray();
        }

        $values = array_slice($row->toArray(), 0, count($this->headers));

        while (count($values) < count($this->headers)) {
            $values[] = '';
        }

        return array_combine($this->headers, $values);
    }

    public function __destruct()
    {
        $this->reader->close();
    }
}
