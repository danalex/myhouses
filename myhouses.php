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