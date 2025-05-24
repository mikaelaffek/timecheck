/**
 * Enhanced error logging utility for Timetjek application
 * Provides comprehensive error logging and handling
 */

/**
 * Safely stringify an object for logging
 * Handles circular references and other serialization issues
 */
export const safeStringify = (obj: any): string => {
  const seen = new WeakSet();
  return JSON.stringify(obj, (key, value) => {
    if (typeof value === 'object' && value !== null) {
      if (seen.has(value)) {
        return '[Circular Reference]';
      }
      seen.add(value);
    }
    return value;
  }, 2);
};

/**
 * Enhanced error logger that handles various error types
 */
export const logError = (error: any, context: string = ''): void => {
  // Create a detailed error object
  const errorDetails: Record<string, any> = {
    timestamp: new Date().toISOString(),
    context: context || 'Unknown context',
  };

  // Extract error information based on error type
  if (error instanceof Error) {
    errorDetails.name = error.name;
    errorDetails.message = error.message;
    errorDetails.stack = error.stack;
  } else if (typeof error === 'string') {
    errorDetails.message = error;
  } else if (error && typeof error === 'object') {
    // Try to extract useful properties from the error object
    Object.keys(error).forEach(key => {
      try {
        // Skip functions and circular references
        if (typeof error[key] !== 'function') {
          errorDetails[key] = error[key];
        }
      } catch (e) {
        errorDetails[`${key}_error`] = 'Could not extract property';
      }
    });
  } else {
    errorDetails.rawError = String(error);
  }

  // Log the detailed error
  console.error(`[ERROR] ${context ? `[${context}] ` : ''}Error details:`, errorDetails);
};

/**
 * Initialize global error handlers
 */
export const initializeErrorHandlers = (): void => {
  // Handle uncaught errors
  window.addEventListener('error', (event) => {
    logError(event.error || event.message, 'Uncaught Error');
    return false; // Allow default error handling to continue
  });

  // Handle unhandled promise rejections
  window.addEventListener('unhandledrejection', (event) => {
    logError(event.reason, 'Unhandled Promise Rejection');
    return false; // Allow default error handling to continue
  });

  // Override console.error to provide better formatting for objects
  const originalConsoleError = console.error;
  console.error = function(...args) {
    // If the first argument is a string and contains "[ERROR]", we've already formatted it
    if (typeof args[0] === 'string' && args[0].includes('[ERROR]')) {
      originalConsoleError.apply(console, args);
      return;
    }

    // Format objects for better readability
    const formattedArgs = args.map(arg => {
      if (arg === null) return 'null';
      if (arg === undefined) return 'undefined';
      if (typeof arg === 'object') {
        try {
          return safeStringify(arg);
        } catch (e) {
          return '[Object that could not be stringified]';
        }
      }
      return arg;
    });

    originalConsoleError.apply(console, formattedArgs);
  };

  console.log('Global error handlers initialized');
};
