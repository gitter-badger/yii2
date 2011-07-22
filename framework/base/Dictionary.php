<?php
/**
 * Dictionary class file.
 *
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2012 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * Dictionary implements a collection that stores key-value pairs.
 *
 * You can access, add or remove an item with a key by using
 * [[itemAt]], [[add]], and [[remove]].
 *
 * To get the number of the items in the dictionary, use [[getCount]].
 *
 * Because Dictionary implements a set of SPL interfaces, it can be used
 * like a regular PHP array as follows,
 *
 * ~~~php
 * $dictionary[$key] = $value;           // add a key-value pair
 * unset($dictionary[$key]);             // remove the value with the specified key
 * if (isset($dictionary[$key]))         // if the dictionary contains the key
 * foreach ($dictionary as $key=>$value) // traverse the items in the dictionary
 * $n = count($dictionary);              // returns the number of items in the dictionary
 * ~~~
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Dictionary extends Component implements \IteratorAggregate, \ArrayAccess, \Countable
{
	/**
	 * @var boolean whether this vector is read-only or not.
	 * If the vector is read-only, adding or moving items will throw an exception.
	 */
	public $readOnly;
	/**
	 * @var array internal data storage
	 */
	private $_d = array();

	/**
	 * Constructor.
	 * Initializes the dictionary with an array or an iterable object.
	 * @param mixed $data the initial data to be populated into the dictionary.
	 * This can be an array or an iterable object.
	 * @param boolean $readOnly whether the dictionary is read-only
	 * @throws Exception if data is not well formed (neither an array nor an iterable object)
	 */
	public function __construct($data = array(), $readOnly = false)
	{
		if ($data !== array()) {
			$this->copyFrom($data);
		}
		$this->readOnly = $readOnly;
	}

	/**
	 * Returns an iterator for traversing the items in the dictionary.
	 * This method is required by the SPL interface `IteratorAggregate`.
	 * It will be implicitly called when you use `foreach` to traverse the dictionary.
	 * @return DictionaryIterator an iterator for traversing the items in the dictionary.
	 */
	public function getIterator()
	{
		return new DictionaryIterator($this->_d);
	}

	/**
	 * Returns the number of items in the dictionary.
	 * This method is required by the SPL `Countable` interface.
	 * It will be implicitly called when you use `count($dictionary)`.
	 * @return integer number of items in the dictionary.
	 */
	public function count()
	{
		return $this->getCount();
	}

	/**
	 * Returns the number of items in the dictionary.
	 * @return integer the number of items in the dictionary
	 */
	public function getCount()
	{
		return count($this->_d);
	}

	/**
	 * Returns the keys stored in the dictionary.
	 * @return array the key list
	 */
	public function getKeys()
	{
		return array_keys($this->_d);
	}

	/**
	 * Returns the item with the specified key.
	 * @param mixed $key the key
	 * @return mixed the element with the specified key.
	 * Null if the key cannot be found in the dictionary.
	 */
	public function itemAt($key)
	{
		return isset($this->_d[$key]) ? $this->_d[$key] : null;
	}

	/**
	 * Adds an item into the dictionary.
	 * Note, if the specified key already exists, the old value will be overwritten.
	 * @param mixed $key key
	 * @param mixed $value value
	 * @throws Exception if the dictionary is read-only
	 */
	public function add($key, $value)
	{
		if (!$this->readOnly) {
			if ($key === null) {
				$this->_d[] = $value;
			}
			else {
				$this->_d[$key] = $value;
			}
		}
		else {
			throw new Exception('Dictionary is read only.');
		}
	}

	/**
	 * Removes an item from the dictionary by its key.
	 * @param mixed $key the key of the item to be removed
	 * @return mixed the removed value, null if no such key exists.
	 * @throws Exception if the dictionary is read-only
	 */
	public function remove($key)
	{
		if (!$this->readOnly) {
			if (isset($this->_d[$key])) {
				$value = $this->_d[$key];
				unset($this->_d[$key]);
				return $value;
			}
			else { // the value is null
				unset($this->_d[$key]);
				return null;
			}
		}
		else {
			throw new Exception('Dictionary is read only.');
		}
	}

	/**
	 * Removes all items in the dictionary.
	 */
	public function clear()
	{
		foreach (array_keys($this->_d) as $key) {
			$this->remove($key);
		}
	}

	/**
	 * Returns a value indicating whether the dictionary contains the specified key.
	 * @param mixed $key the key
	 * @return boolean whether the dictionary contains an item with the specified key
	 */
	public function contains($key)
	{
		return isset($this->_d[$key]) || array_key_exists($key, $this->_d);
	}

	/**
	 * Returns the dictionary as a PHP array.
	 * @return array the list of items in array
	 */
	public function toArray()
	{
		return $this->_d;
	}

	/**
	 * Copies iterable data into the dictionary.
	 * Note, existing data in the dictionary will be cleared first.
	 * @param mixed $data the data to be copied from, must be an array or an object implementing `Traversable`
	 * @throws Exception if data is neither an array nor an iterator.
	 */
	public function copyFrom($data)
	{
		if (is_array($data) || $data instanceof \Traversable)
		{
			if ($this->_d !== array()) {
				$this->clear();
			}
			if ($data instanceof self) {
				$data = $data->_d;
			}
			foreach ($data as $key => $value) {
				$this->add($key, $value);
			}
		}
		else {
			throw new Exception('Data must be either an array or an object implementing Traversable.');
		}
	}

	/**
	 * Merges iterable data into the dictionary.
	 *
	 * Existing elements in the dictionary will be overwritten if their keys are the same as those in the source.
	 * If the merge is recursive, the following algorithm is performed:
	 * <ul>
	 * <li>the dictionary data is saved as $a, and the source data is saved as $b;</li>
	 * <li>if $a and $b both have an array indxed at the same string key, the arrays will be merged using this algorithm;</li>
	 * <li>any integer-indexed elements in $b will be appended to $a and reindxed accordingly;</li>
	 * <li>any string-indexed elements in $b will overwrite elements in $a with the same index;</li>
	 * </ul>
	 *
	 * @param mixed $data the data to be merged with, must be an array or object implementing Traversable
	 * @param boolean $recursive whether the merging should be recursive.
	 *
	 * @throws CException If data is neither an array nor an iterator.
	 */
	public function mergeWith($data, $recursive=true)
	{
		if (is_array($data) || $data instanceof \Traversable) {
			if ($data instanceof self) {
				$data = $data->_d;
			}
			if ($recursive) {
				if ($data instanceof \Traversable) {
					$d=array();
					foreach($data as $key => $value) {
						$d[$key] = $value;
					}
					$this->_d = self::mergeArray($this->_d, $d);
				}
				else {
					$this->_d = self::mergeArray($this->_d, $data);
				}
			}
			else {
				foreach($data as $key => $value) {
					$this->add($key, $value);
				}
			}
		}
		else {
			throw new Exception('Dictionary data must be an array or an object implementing Traversable.');
		}
	}

	/**
	 * Returns whether there is an element at the specified offset.
	 * This method is required by the SPL interface `ArrayAccess`.
	 * It is implicitly called when you use something like `isset($dictionary[$offset])`.
	 * This is equivalent to [[contains]].
	 * @param mixed $offset the offset to check on
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return $this->contains($offset);
	}

	/**
	 * Returns the element at the specified offset.
	 * This method is required by the SPL interface `ArrayAccess`.
	 * It is implicitly called when you use something like `$value = $dictionary[$offset];`.
	 * This is equivalent to [[itemAt]].
	 * @param mixed $offset the offset to retrieve element.
	 * @return mixed the element at the offset, null if no element is found at the offset
	 */
	public function offsetGet($offset)
	{
		return $this->itemAt($offset);
	}

	/**
	 * Sets the element at the specified offset.
	 * This method is required by the SPL interface `ArrayAccess`.
	 * It is implicitly called when you use something like `$dictionary[$offset] = $item;`.
	 * If the offset is null, the new item will be appended to the dictionary.
	 * Otherwise, the existing item at the offset will be replaced with the new item.
	 * This is equivalent to [[add]].
	 * @param mixed $offset the offset to set element
	 * @param mixed $item the element value
	 */
	public function offsetSet($offset, $item)
	{
		$this->add($offset,$item);
	}

	/**
	 * Unsets the element at the specified offset.
	 * This method is required by the SPL interface `ArrayAccess`.
	 * It is implicitly called when you use something like `unset($dictionary[$offset])`.
	 * This is equivalent to [[remove]].
	 * @param mixed $offset the offset to unset element
	 */
	public function offsetUnset($offset)
	{
		$this->remove($offset);
	}

	/**
	 * Merges two arrays into one recursively.
	 * If each array has an element with the same string key value, the latter
	 * will overwrite the former (different from array_merge_recursive).
	 * Recursive merging will be conducted if both arrays have an element of array
	 * type and are having the same key.
	 * For integer-keyed elements, the elements from the latter array will
	 * be appended to the former array.
	 * @param array $a array to be merged to
	 * @param array $b array to be merged from
	 * @return array the merged array (the original arrays are not changed.)
	 * @see mergeWith
	 */
	public static function mergeArray($a, $b)
	{
		foreach($b as $k=>$v) {
			if(is_integer($k)) {
				isset($a[$k]) ? $a[] = $v : $a[$k] = $v;
			}
			elseif(is_array($v) && isset($a[$k]) && is_array($a[$k])) {
				$a[$k] = self::mergeArray($a[$k], $v);
			}
			else
				$a[$k] = $v;
		}
		return $a;
	}
}