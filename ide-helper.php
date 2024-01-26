<?php

/**
 * Log module call.
 *
 * @param string $module The name of the module
 * @param string $action The name of the action being performed
 * @param string|array $requestString The input parameters for the API call
 * @param string|array $responseData The response data from the API call
 * @param string|array $processedData The resulting data after any post processing (eg. json decode, xml decode, etc...)
 * @param array $replaceVars An array of strings for replacement
 */
function logModuleCall(
    string $module,
    string $action,
    string|array $requestString,
    string|array $responseData,
    string|array $processedData,
    array $replaceVars
) {}

/**
 * Log activity.
 *
 * @param string $message The message to log
 * @param int $userId An optional user id to which the log entry relates
 */
function logActivity(string $message, int $userId = 0) {}

/**
 * Add a hook
 *
 * @param string   $hookName
 * @param int      $priority
 * @param callable $callable
 *
 * @return void
 */
function add_hook(string $hookName, int $priority, callable $callable) {}
