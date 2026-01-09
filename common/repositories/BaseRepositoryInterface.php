<?php
namespace common\repositories;

interface BaseRepositoryInterface{

	public function save(object $entity): void;
    public function delete(object $entity): void;

}