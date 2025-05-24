// This is a temporary file to fix the fetchRecentRegistrations function
// We'll use this to see the proper structure before making changes to the actual file

const fetchRecentRegistrations = async () => {
  loading.value = true;
  try {
    console.log('Fetching recent time registrations...');
    
    // Ensure the authorization header is set
    const token = localStorage.getItem('token');
    if (token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    }
    
    try {
      const response = await axios.get('/api/time-registrations/recent');
      console.log('Recent registrations response:', response.data);
      recentRegistrations.value = response.data;
    } catch (apiError) {
      // Use our error handler utility to properly log the error
      handleApiError(apiError, 'Dashboard.fetchRecentRegistrations');
      console.log('Using mock data for time registrations');
      
      // Use mock data if API fails
      const today = new Date().toISOString().split('T')[0];
      const yesterday = new Date();
      yesterday.setDate(yesterday.getDate() - 1);
      const yesterdayStr = yesterday.toISOString().split('T')[0];
      
      const twoDaysAgo = new Date();
      twoDaysAgo.setDate(twoDaysAgo.getDate() - 2);
      const twoDaysAgoStr = twoDaysAgo.toISOString().split('T')[0];
      
      recentRegistrations.value = [
        {
          id: 1001,
          user_id: 1,
          date: today,
          clock_in: '08:30:00',
          clock_out: null,
          total_hours: null,
          status: 'pending'
        },
        {
          id: 1002,
          user_id: 1,
          date: yesterdayStr,
          clock_in: '08:15:00',
          clock_out: '17:00:00',
          total_hours: 8.75,
          status: 'approved'
        },
        {
          id: 1003,
          user_id: 1,
          date: twoDaysAgoStr,
          clock_in: '09:00:00',
          clock_out: '18:30:00',
          total_hours: 9.5,
          status: 'approved'
        }
      ];
    }
    
    // Check if user is currently clocked in
    const today = new Date().toISOString().split('T')[0];
    const todayRegistration = recentRegistrations.value.find(
      reg => {
        // Check if the date matches today (might be in different formats)
        const regDate = new Date(reg.date).toISOString().split('T')[0];
        return regDate === today && reg.clock_out === null;
      }
    );
    
    if (todayRegistration) {
      console.log('Found active clock-in for today:', todayRegistration);
      isClockedIn.value = true;
      lastClockIn.value = todayRegistration.clock_in;
    } else {
      console.log('No active clock-in found for today');
      isClockedIn.value = false;
      lastClockIn.value = null;
    }

    // Calculate statistics
    calculateStatistics();
  } catch (error) {
    // Use our error handler utility for the outer try-catch block
    handleApiError(error, 'Dashboard.fetchRecentRegistrations.outer');
    // Initialize with empty array to avoid errors in the UI
    recentRegistrations.value = [];
  } finally {
    loading.value = false;
  }
}
