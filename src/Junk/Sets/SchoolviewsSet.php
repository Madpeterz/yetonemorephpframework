<?php

namespace YAPF\Junk\Sets;

use YAPF\DbObjects\CollectionSet\CollectionSet as CollectionSet;
use YAPF\Junk\Models\Schoolviews as Schoolviews;

// Do not edit this file, rerun gen.php to update!
class SchoolviewsSet extends CollectionSet
{
	public function __construct()
	{
		parent::__construct("YAPF\Junk\Models\Schoolviews");
	}
	/**
	 * getObjectByID
	 * returns a object that matchs the selected id
	 * returns null if not found
	 * Note: Does not support bad Ids please use findObjectByField
	 */
	public function getObjectByID($id): ?Schoolviews
	{
		return parent::getObjectByID($id);
	}
	/**
	 * getFirst
	 * returns the first object in a collection
	 */
	public function getFirst(): ?Schoolviews
	{
		return parent::getFirst();
	}
	/**
	 * getObjectByField
	 * returns the first object in a collection that matchs the field and value checks
	 */
	public function getObjectByField(string $fieldname, $value): ?Schoolviews
	{
		return parent::getObjectByField($fieldname, $value);
	}
	/**
	 * current
	 * used by foreach to get the object should not be called directly
	 */
	public function current(): Schoolviews
	{
		return parent::current();
	}
	/**
	 * uniqueIds
	 * returns unique values from the collection matching that field
	 * @return array<int>
	 */
	public function uniqueIds(): array
	{
		return parent::uniqueArray("id");
	}
	/**
	 * uniqueSchoolLinks
	 * returns unique values from the collection matching that field
	 * @return array<int>
	 */
	public function uniqueSchoolLinks(): array
	{
		return parent::uniqueArray("schoolLink");
	}
	/**
	 * uniqueViewNames
	 * returns unique values from the collection matching that field
	 * @return array<string>
	 */
	public function uniqueViewNames(): array
	{
		return parent::uniqueArray("viewName");
	}
	/**
	 * uniqueDisplayNames
	 * returns unique values from the collection matching that field
	 * @return array<string>
	 */
	public function uniqueDisplayNames(): array
	{
		return parent::uniqueArray("displayName");
	}
	/**
	 * uniquePreviewUrls
	 * returns unique values from the collection matching that field
	 * @return array<string>
	 */
	public function uniquePreviewUrls(): array
	{
		return parent::uniqueArray("previewUrl");
	}
	// Loaders
	/**
	 * loadById
	 * @return mixed[] [status =>  bool, count => integer, message =>  string]
	*/
	public function loadById(
		int $id, 
		int $limit = 0, 
		string $orderBy = "id", 
		string $orderDir = "DESC"
	): array
	{
		return $this->loadByField(
			"id", 
			$id, 
			$limit, 
			$orderBy, 
			$orderDir
		);
	}
	/**
	 * loadFromIds
	 * @return array<mixed> [status =>  bool, count => integer, message =>  string]
	*/
	public function loadFromIds(array $values): array
	{
		return $this->loadIndexs("id", $values);
	}
	/**
	 * loadBySchoolLink
	 * @return mixed[] [status =>  bool, count => integer, message =>  string]
	*/
	public function loadBySchoolLink(
		int $schoolLink, 
		int $limit = 0, 
		string $orderBy = "id", 
		string $orderDir = "DESC"
	): array
	{
		return $this->loadByField(
			"schoolLink", 
			$schoolLink, 
			$limit, 
			$orderBy, 
			$orderDir
		);
	}
	/**
	 * loadFromSchoolLinks
	 * @return array<mixed> [status =>  bool, count => integer, message =>  string]
	*/
	public function loadFromSchoolLinks(array $values): array
	{
		return $this->loadIndexs("schoolLink", $values);
	}
	/**
	 * loadByViewName
	 * @return mixed[] [status =>  bool, count => integer, message =>  string]
	*/
	public function loadByViewName(
		string $viewName, 
		int $limit = 0, 
		string $orderBy = "id", 
		string $orderDir = "DESC"
	): array
	{
		return $this->loadByField(
			"viewName", 
			$viewName, 
			$limit, 
			$orderBy, 
			$orderDir
		);
	}
	/**
	 * loadFromViewNames
	 * @return array<mixed> [status =>  bool, count => integer, message =>  string]
	*/
	public function loadFromViewNames(array $values): array
	{
		return $this->loadIndexs("viewName", $values);
	}
	/**
	 * loadByDisplayName
	 * @return mixed[] [status =>  bool, count => integer, message =>  string]
	*/
	public function loadByDisplayName(
		string $displayName, 
		int $limit = 0, 
		string $orderBy = "id", 
		string $orderDir = "DESC"
	): array
	{
		return $this->loadByField(
			"displayName", 
			$displayName, 
			$limit, 
			$orderBy, 
			$orderDir
		);
	}
	/**
	 * loadFromDisplayNames
	 * @return array<mixed> [status =>  bool, count => integer, message =>  string]
	*/
	public function loadFromDisplayNames(array $values): array
	{
		return $this->loadIndexs("displayName", $values);
	}
	/**
	 * loadByPreviewUrl
	 * @return mixed[] [status =>  bool, count => integer, message =>  string]
	*/
	public function loadByPreviewUrl(
		string $previewUrl, 
		int $limit = 0, 
		string $orderBy = "id", 
		string $orderDir = "DESC"
	): array
	{
		return $this->loadByField(
			"previewUrl", 
			$previewUrl, 
			$limit, 
			$orderBy, 
			$orderDir
		);
	}
	/**
	 * loadFromPreviewUrls
	 * @return array<mixed> [status =>  bool, count => integer, message =>  string]
	*/
	public function loadFromPreviewUrls(array $values): array
	{
		return $this->loadIndexs("previewUrl", $values);
	}
	// Related loaders
	public function relatedSchools(): SchoolsSet
	{
		$ids = $this->uniqueSchoolLinks();
		$collection = new SchoolsSet();
		$collection->loadFromIds($ids);
		return $collection;
	}
}
