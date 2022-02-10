<?php

namespace YAPF\Junk\Models;

use YAPF\DbObjects\GenClass\GenClass as GenClass;
use YAPF\Junk\Sets\SchoolsSet as SchoolsSet;

// Do not edit this file, rerun gen.php to update!
class Schoolvideos extends genClass
{
	protected $use_table = "test.schoolvideos";
	// Data Design
	protected $fields = [
		"id",
		"schoolLink",
		"videoName",
		"previewUrl",
		"displayName",
	];
	protected $dataset = [
		"id" => ["type" => "int", "value" => null],
		"schoolLink" => ["type" => "int", "value" => null],
		"videoName" => ["type" => "str", "value" => null],
		"previewUrl" => ["type" => "str", "value" => null],
		"displayName" => ["type" => "str", "value" => null],
	];
	// Setters
	/**
	* setSchoolLink
	* @return mixed[] [status =>  bool, message =>  string]
	*/
	public function setSchoolLink(?int $newvalue): array
	{
		return $this->updateField("schoolLink", $newvalue);
	}
	/**
	* setVideoName
	* @return mixed[] [status =>  bool, message =>  string]
	*/
	public function setVideoName(?string $newvalue): array
	{
		return $this->updateField("videoName", $newvalue);
	}
	/**
	* setPreviewUrl
	* @return mixed[] [status =>  bool, message =>  string]
	*/
	public function setPreviewUrl(?string $newvalue): array
	{
		return $this->updateField("previewUrl", $newvalue);
	}
	/**
	* setDisplayName
	* @return mixed[] [status =>  bool, message =>  string]
	*/
	public function setDisplayName(?string $newvalue): array
	{
		return $this->updateField("displayName", $newvalue);
	}
	// Getters
	public function getSchoolLink(): ?int
	{
		return $this->getField("schoolLink");
	}
	public function getVideoName(): ?string
	{
		return $this->getField("videoName");
	}
	public function getPreviewUrl(): ?string
	{
		return $this->getField("previewUrl");
	}
	public function getDisplayName(): ?string
	{
		return $this->getField("displayName");
	}
	// Loaders
	public function loadBySchoolLink(int $schoolLink): bool
	{
		return $this->loadByField(
			"schoolLink",
			$schoolLink
		);
	}
	public function loadByVideoName(string $videoName): bool
	{
		return $this->loadByField(
			"videoName",
			$videoName
		);
	}
	public function loadByPreviewUrl(string $previewUrl): bool
	{
		return $this->loadByField(
			"previewUrl",
			$previewUrl
		);
	}
	public function loadByDisplayName(string $displayName): bool
	{
		return $this->loadByField(
			"displayName",
			$displayName
		);
	}
	public function relatedSchools(): SchoolsSet
	{
		$ids = [$this->getSchoolLink()];
		$collection = new SchoolsSet();
		$collection->loadFromIds($ids);
		return $collection;
	}
}
// please do not edit this file
