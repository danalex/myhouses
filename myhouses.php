<?php 
echo "Commands:\n";
echo "  ADD <sale/rent> <price_gbp> <address> <bedrooms>\n";
echo "    Add an item to the entries. Use underscore for spaces in the address field \n";
echo "  SEARCH <by_what> <what>\n";
echo "    Search the items for an entry that mathces the query.\n";
echo "  EXIT\n";
echo "    Exit the CLI.\n";

// Iterator that filters entries by a specific field-value pair.
class EntryFilter extends FilterIterator
{
    private $field;
    private $value_;

    public function __construct(Iterator $iterator, $field, $value)
    {
        parent::__construct($iterator);
        $this->field = $field;
        $this->value_ = $value;
    }

    //Overrides the FilterIterator's method. Checks to see if the current element matches the search query
    public function accept()
    {
        $entry = $this->current();
        if (strcasecmp($entry[$this->field], $this->value_) == 0) {
            return true;
        }
        return false;
    }
}

//Maintains the file containing the entries.
class Entries
{
    private $path;
    private $entries;
    private $modified;

    public function __construct($path)
    {
        $this->path = $path;
        $this->entries = new SplQueue();
        $this->modified = false;
    }

    public function open()
    {
        $file_info = new SplFileInfo($this->path); //Gets an SplFileObject object for the file
        $opened = false;
        if ($file_info->isReadable()) {
            $file = $file_info->openFile("r") or die("Could not open file.");

            $file->setFlags(SplFileObject::READ_CSV); //Set the READ_CSV flag so the file can parse the CSV entries automatically.
            foreach ($file as $row) {
                $this->add_entry($row);
            }
            $opened = true;
        }
        $this->modified = false;
        return $opened;
    }

    public function count()
    {
        return count($this->entries);
    }

    public function add_entry($args)
    {
        if (count($args) != 4) {
            return false;
        }
        $entry = array(
            "sale/rent" => trim($args[0]),
            "price_gbp" => trim($args[1]),
            "address" => trim($args[2]),
            "bedrooms" => trim($args[3])
        );
        $this->entries->enqueue($entry);
        $this->modified = true;
        return true;
    }

    public function search($field, $value)
    {
        return new EntryFilter($this->entries, $field, $value);
    }

    public function is_modified()
    {
        return $this->modified;
    }

    public function save()
    {
        if (!$this->modified) {
            return;
        }
        $file = new SplFileObject($this->path, "w");
        foreach ($this->entries as $entry) {
            $file->fputcsv([$entry["sale/rent"], $entry["price_gbp"], $entry["address"], $entry["bedrooms"]]);
        }
    }
}

$entries = new Entries("file.csv");

if ($entries->open()) {
    echo "Opened file.\n";
    echo "Read ", $entries->count(), " entries.\n";
}

$command = [""];
$stdin = fopen("php://stdin", "r");
while (!feof($stdin)) {
    echo " > ";
    $input = fgets($stdin);
    $command = explode(" ", trim($input));

    $command[0] = strtolower($command[0]);

    $must_break = false;
    switch ($command[0]) {
        case "add":
            if (count($command) != 5) {
                echo "Wrong number of arguments for ADD.\n";
                continue;
            }
            $entries->add_entry(array_slice($command, 1));
            break;
        case "search":
            if (count($command) != 3) {
                echo "Wrong number of argments for SEARCH.\n";
                continue;
            }
            $iterator = $entries->search($command[1], $command[2]);
            foreach ($iterator as $entry) {
                printf("%s, %s, %s, %s\n", $entry["sale/rent"], $entry["price_gbp"], $entry["address"], $entry["bedrooms"]);
            }
            break;
        case "exit":
            $must_break = true;
            break;
        default:
            echo "Unrecognized command.\n";
    }

    if ($must_break) {
        break;
    }
}

if ($entries->is_modified()) {
    echo "File has been modified. Do you want to save? [Y/n]";
    $command = strtolower(trim(fgets($stdin)));
    if ($command != "n") {
        $entries->save();
        echo "File saved.\n";
    }
}

echo "Thank you. Have a nice day! \n";
?>