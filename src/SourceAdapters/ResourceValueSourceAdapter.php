<?php

namespace Reno\FiltersAdapter\SourceAdapters;

use Filters\DTO\Range;
use Illuminate\Support\Collection;
use Reno\Cms\Models\ResourceValue;
use Filters\Interfaces\UsesTitles;
use Reno\Cms\Containers\FieldContainer;
use Illuminate\Database\Eloquent\Builder;
use Filters\Interfaces\SourceAdapterInterface;
use Filters\Interfaces\SupportsRangeInterface;
use Reno\Cms\Interfaces\FieldTypes\HasOptions;
use Filters\Interfaces\SupportsValuesListInterface;

class ResourceValueSourceAdapter implements SourceAdapterInterface, SupportsValuesListInterface, SupportsRangeInterface, UsesTitles
{
    public function __construct(
        protected FieldContainer $fieldContainer,
    )
    {
    }

    public function getRange(Builder $query): Range
    {
        $data = ResourceValue::query()
            ->selectRaw('MIN(value) as _min, MAX(value) as _max')
            ->where('resource_field_id', $this->fieldContainer->getId())
            ->whereExists($query->whereColumn('id', '=', 'resource_id'))
            ->first();

        return new Range(
            min: $data->_min ?? null,
            max: $data->_max ?? null,
        );
    }

    public function applyRangeToQuery(Builder $query, ?Range $range): Builder
    {
        return $query->whereHas('resourceValues', function (Builder $query) use ($range) {
            $query
                ->where('resource_field_id', $this->fieldContainer->getId())
                ->when($range?->min, function (Builder $query) use ($range) {
                    return $query->where('value', '>=', $range->min);
                })
                ->when($range?->max, function (Builder $query) use ($range) {
                    return $query->where('value', '<=', $range->max);
                });
        });
    }

    public function getValues(Builder $query): array
    {
        return ResourceValue::query()
            ->distinct()
            ->selectRaw('value')
            ->where('resource_field_id', $this->fieldContainer->getId())
            ->whereExists($query->whereColumn('id', '=', 'resource_id'))
            ->pluck('value', 'value')
            ->filter()
            ->toArray();
    }

    public function applyValuesToQuery(Builder $query, ?Collection $values): Builder
    {
        $values = $values?->values()->toArray();

        return $query->when(!empty($values), function (Builder $query) use ($values) {
            $query->whereHas('resourceValues', function (Builder $query) use ($values) {
                $query
                    ->where('resource_field_id', $this->fieldContainer->getId())
                    ->whereIn('value', $values);
            });
        });
    }

    public function getTitles(array $ids): Collection
    {
        $field = $this->fieldContainer->getField();
        $titles = [];

        if ($field instanceof HasOptions) {
            $titles = $field->getOptions();
        }

        return Collection::make($titles);
    }
}
