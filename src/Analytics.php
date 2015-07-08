<?php

namespace Kurt\Google;

use Kurt\Google\Traits\Filters\CustomCommonFilters;
use Kurt\Google\Traits\Filters\GoogleCommonFilters;

use Kurt\Google\Traits\Handlers\DatesHandler;
use Kurt\Google\Traits\Handlers\DimensionsHandler;
use Kurt\Google\Traits\Handlers\FiltersHandler;
use Kurt\Google\Traits\Handlers\MetricsHandler;
use Kurt\Google\Traits\Handlers\ParamsHandler;
use Kurt\Google\Traits\Handlers\SegmentHandler;
use Kurt\Google\Traits\Handlers\SortHandler;

use Kurt\Google\Traits\HelperFunctions;

class Analytics {

	use HelperFunctions;
	use CustomCommonFilters, GoogleCommonFilters;
	use DatesHandler, DimensionsHandler, FiltersHandler, MetricsHandler, ParamsHandler, SegmentHandler, SortHandler;


	private $googleServicesCore;

	private $analyticsViewId; 

	private $metrics = [];
	private $dimensions = [];
	private $sort;
	private $filters;
	private $segment;

	private $startDate;
	private $endDate;

	private $service;

	function __construct(Core $googleServicesCore) {

		$this->googleServicesCore = $googleServicesCore;

		$this->setPropertiesFromConfigFile();

		$this->setupAnalyticsService();

		$this->setupDates();

	}

	private function setPropertiesFromConfigFile()
	{
		$this->analyticsViewId = config('google.analytics.analyticsViewId');
	}

	private function setupAnalyticsService()
	{
		// Create Google Service Analytics object with our preconfigured Google_Client
		$this->service = new \Google_Service_Analytics(
			$this->googleServicesCore->getClient()
		);
	}

	private function setupDates()
	{
		$this->startDate = date('Y-m-d', strtotime('-1 month'));
	
		$this->endDate = date('Y-m-d');
	}

	/**
	 * Execute the query and fetch the results to a collection.
	 * 
	 * @return Illuminate\Support\Collection
	 */
	private function getData()
	{
		/**
		 * A query can't run without any metrics.
		 */
		if (! $this->metricsAreSet()) {
			throw new \Exception("No metrics specified.", 1);
		}
		
		$data = $this->service->data_ga->get(
			$this->analyticsViewId, 
			$this->startDate, 
			$this->endDate, 
			$this->getMetricsAsString(), 
			$this->getOptions()
		);

		$headers = $data->getColumnHeaders();

		foreach ($data->getRows() as $rowKey => $rowDatas) {

			foreach ($rowDatas as $dataKey => $rowData) {

				$results[$rowKey][$headers[$dataKey]->name] = $rowData;

			}

		}

		$results = $this->dimentionsAreSet() ? $results : $results[0];

		return collect($results);
	}

	/**
	 * Execute the query and fetch the results to a collection.
	 * 
	 * @return Illuminate\Support\Collection
	 */
	public function getRealtimeData()
	{
		$data = $this->service->data_realtime->get(
			$this->analyticsViewId, 
			'rt:activeUsers', 
			$this->getOptions()
		);

		return $data->getRows();
	}

	/**
	 * Execute the query by merging arrays to current ones.
	 * 
	 * @param  array  $parameters 
 	 * @return Illuminate\Support\Collection
	 */
	public function execute($parameters = [])
	{
		$this->mergeParams($parameters);

		return $this->getData();
	}

}