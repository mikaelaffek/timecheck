import { ref } from 'vue'
import axios, { AxiosRequestConfig, AxiosResponse, AxiosError } from 'axios'

interface SnackbarState {
  show: boolean
  text: string
  color: string
  timeout: number
}

interface ApiOptions {
  showSuccessNotification?: boolean
  successMessage?: string
}

export function useApi() {
  // Create a reactive snackbar state
  const snackbar = ref<SnackbarState>({
    show: false,
    text: '',
    color: 'success',
    timeout: 3000
  })

  // Loading state
  const loading = ref(false)

  // Handle API errors
  const handleApiError = (error: AxiosError, source: string) => {
    console.error(`Error in ${source}:`, error)
    
    // Check for specific error types
    if (error.response) {
      // Server responded with an error status
      if (error.response.status === 422 && error.response.data?.message) {
        // Validation error - show the specific message
        snackbar.value.text = error.response.data.message
        snackbar.value.color = 'warning'
      } else if (error.response.status === 401) {
        // Unauthorized - likely token expired
        snackbar.value.text = 'Your session has expired. Please log in again.'
        snackbar.value.color = 'error'
        // Could redirect to login here
      } else {
        // Other server errors
        snackbar.value.text = `Server error: ${error.response.data?.message || 'Unknown error'}`
        snackbar.value.color = 'error'
      }
    } else if (error.request) {
      // Request was made but no response received
      snackbar.value.text = 'No response from server. Please check your connection.'
      snackbar.value.color = 'error'
    } else {
      // Error in setting up the request
      snackbar.value.text = `Error: ${error.message}`
      snackbar.value.color = 'error'
    }
    
    // Show the snackbar
    snackbar.value.show = true
  }

  // Generic API request function
  const apiRequest = async <T>(
    method: string,
    url: string,
    data?: any,
    options: ApiOptions = {},
    config?: AxiosRequestConfig
  ): Promise<T | null> => {
    loading.value = true
    
    try {
      let response: AxiosResponse<T>
      
      switch (method.toLowerCase()) {
        case 'get':
          response = await axios.get<T>(url, config)
          break
        case 'post':
          response = await axios.post<T>(url, data, config)
          break
        case 'put':
          response = await axios.put<T>(url, data, config)
          break
        case 'delete':
          response = await axios.delete<T>(url, config)
          break
        default:
          throw new Error(`Unsupported method: ${method}`)
      }
      
      // Show success notification if requested
      if (options.showSuccessNotification) {
        snackbar.value.text = options.successMessage || 'Operation completed successfully'
        snackbar.value.color = 'success'
        snackbar.value.show = true
      }
      
      return response.data
    } catch (error) {
      handleApiError(error as AxiosError, `apiRequest:${method}:${url}`)
      return null
    } finally {
      loading.value = false
    }
  }

  // Convenience methods
  const get = <T>(url: string, options?: ApiOptions, config?: AxiosRequestConfig) => 
    apiRequest<T>('get', url, undefined, options, config)
  
  const post = <T>(url: string, data?: any, options?: ApiOptions, config?: AxiosRequestConfig) => 
    apiRequest<T>('post', url, data, options, config)
  
  const put = <T>(url: string, data?: any, options?: ApiOptions, config?: AxiosRequestConfig) => 
    apiRequest<T>('put', url, data, options, config)
  
  const del = <T>(url: string, options?: ApiOptions, config?: AxiosRequestConfig) => 
    apiRequest<T>('delete', url, undefined, options, config)

  return {
    loading,
    snackbar,
    get,
    post,
    put,
    del,
    handleApiError
  }
}
