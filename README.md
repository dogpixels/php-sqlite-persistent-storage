# PHP Sqlite3 Persistent Key-Value Storage

Provides a very simple persistent key-value storage for PHP through the static class `Storage`.

## Usage

Download storage.php and include it in your PHP script:
```PHP
include('storage.php');
```

Note that upon calling any function for the first time, a database file like `storage.db` will appear next to the script. This is your storage file. Naturally, the process running the script will need write access to the directory the script resides in.

Whenever an operation fails, an exception will be thrown with additional information. Make sure to wrap every call in a `try-else` block:

```PHP
try {
    $data = Storage::get("MyFavoriteBook");
}
catch(Exception $ex) {
    var_dump($ex);
}
```

## Configuration

Configuration is optional, as everything runs out of the box. Look into `storage.php` and check out the following option at the top of the file:

`define('STORAGE_FILE', 'storage.db');` Enables you to change the storage file's path and name.

`define('STORAGE_ENCRYPTION_KEY', '');` Lets you enable encryption. If a key is provided (default is blank for no encryption), the file will be encrypted with that key and must henceforth be used with this very key.

## Store & Retrieve

Data is being stored with `set(string $key, array $data): bool`, which needs a key and data as array:

```PHP
$key = "MyFavoriteBook";

$data = [
    "title": "Rafts",
    "author": "Utunu",
    "pages": 188
];

try {
    $success = Storage::set($key, $data);
}
catch(Exception $ex) {
    var_dump($ex);
}

var_dump($success);
# > true
```

> Note: If the key already exists, its contents will be overwritten; this serves as a way to update your data.

To retrieve some data back with `get(string $key): array` you only need the key:

```PHP
try {
    $data = Storage::get("MyFavoriteBook");
}
catch(Exception $ex) {
    var_dump($ex);
}

var_dump($data);
# > [
# >    "title" => "Rafts",
# >    "author" => "Utunu",
# >    "pages" => 188
# > ]
```

## Count Entries

To get the total number of entries, go with `count(): int`:

```PHP
try {
    $count = Storage::count();
}
catch(Exception $ex) {
    var_dump($ex);
}

var_dump($count);
# > 1
```

## Deleting

Simply call `delete(string $key): bool` with the key in question:

```PHP
try {
    $success = Storage::delete("MyFavoriteBook");
}
catch(Exception $ex) {
    var_dump($ex);
}

var_dump($success);
# > true
```

> Note that `delete()` will always return `true` unless there was a serious failure. Even if no rows were affected (e.g. the provided key did not exist in order to be deleted), `delete()` will still return `true`.

## Pruning

The database keeps track on when an entry was last updated. You can prune all data older than a given timespan with `prune(string $age): bool`:

```PHP
try {
    $success = Storage::prune("3 months");
}
catch(Exception $ex) {
    var_dump($ex);
}

var_dump($success);
# > true
```

> See https://www.sqlite.org/lang_datefunc.html for valid age values.

## License

This library is licensed under the terms of the *GNU Affero General Public License 3.0*,
Copyright draconigen@dogpixels.net.
See the LICENSE file or https://www.gnu.org/licenses/agpl-3.0.en.html for full details.