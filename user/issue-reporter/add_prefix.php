<?php

function addPrefixToClassNames($filePath, $prefix, $outputFilePath = null) {
    // Check if the file exists
    if (!file_exists($filePath)) {
        die("File not found: $filePath");
    }

    // Read the file content
    $content = file_get_contents($filePath);

    // Regular expression to find class names in the HTML code
    $pattern = '/class\s*=\s*"([^"]+)"/';

    // Callback function to modify class names
    $callback = function ($matches) use ($prefix) {
        // Split class names into an array
        $classNames = explode(' ', $matches[1]);

        // Add the prefix to each class name
        $prefixedClassNames = array_map(function ($className) use ($prefix) {
            return $prefix . $className;
        }, $classNames);

        // Join the class names back into a string
        $newClassNames = implode(' ', $prefixedClassNames);

        // Return the modified class attribute
        return 'class="' . $newClassNames . '"';
    };

    // Apply the regular expression and modify the content
    $updatedContent = preg_replace_callback($pattern, $callback, $content);

    // Determine where to save the output
    $outputFilePath = $outputFilePath ?? $filePath;

    // Save the modified content back to the file
    file_put_contents($outputFilePath, $updatedContent);

    echo "Class names updated successfully. Output saved to: $outputFilePath\n";
}

// Example usage
$inputFilePath = 'example.html'; // Path to the input HTML file
$outputFilePath = 'example_modified.html'; // Path to save the modified HTML file
$prefix = 'my-prefix-'; // The prefix to add to class names

addPrefixToClassNames('issue-reporter.php', 'issue-reporter-widget-', 'issue-reporter-new.php');

?>
