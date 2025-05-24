/**
 * Error handling utility for the Timetjek application
 * Provides consistent error handling and logging across the application
 */

import axios, { AxiosError } from 'axios';

/**
 * Format error message for display and logging
 */
export const formatErrorMessage = (error: any): string => {
  if (axios.isAxiosError(error)) {
    const axiosError = error as AxiosError;
    
    // Handle API error responses
    if (axiosError.response) {
      const status = axiosError.response.status;
      const data = axiosError.response.data as any;
      
      // Handle Laravel validation errors
      if (status === 422 && data.errors) {
        const validationErrors = Object.values(data.errors)
          .flat()
          .join(', ');
        return `Validation error: ${validationErrors}`;
      }
      
      // Handle other API errors with messages
      if (data.message) {
        return `API error (${status}): ${data.message}`;
      }
      
      return `API error: ${status} ${axiosError.response.statusText}`;
    }
    
    // Handle network errors
    if (axiosError.request && !axiosError.response) {
      return 'Network error: Could not connect to the server. Please check your connection.';
    }
    
    // Handle other axios errors
    return `Request error: ${axiosError.message}`;
  }
  
  // Handle regular errors
  if (error instanceof Error) {
    return `Error: ${error.message}`;
  }
  
  // Handle unknown errors
  return 'An unknown error occurred';
};

/**
 * Log error with detailed information for debugging
 */
export const logError = (error: any, context: string = ''): void => {
  const errorMessage = formatErrorMessage(error);
  const contextPrefix = context ? `[${context}] ` : '';
  
  console.error(`${contextPrefix}${errorMessage}`);
  
  // Log additional details for axios errors
  if (axios.isAxiosError(error)) {
    const axiosError = error as AxiosError;
    
    console.error('Error details:', {
      url: axiosError.config?.url,
      method: axiosError.config?.method,
      status: axiosError.response?.status,
      statusText: axiosError.response?.statusText,
      data: axiosError.response?.data,
    });
  }
};

/**
 * Handle API errors consistently across the application
 * Returns a formatted error message and logs detailed information
 */
export const handleApiError = (error: any, context: string = ''): string => {
  logError(error, context);
  return formatErrorMessage(error);
};

/**
 * Setup global error handlers
 */
export const setupGlobalErrorHandlers = (): void => {
  // Add global error handler for uncaught exceptions
  window.addEventListener('error', (event) => {
    console.error('Uncaught error:', event.error);
  });
  
  // Add global promise rejection handler
  window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
  });
};
