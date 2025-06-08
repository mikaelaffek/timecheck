import { useApi } from '../../composables/useApi';

export function useTimeTrackingService() {
  const { loading, get, post, put, snackbar } = useApi(); // Removed 'del' as it's not used

  const fetchRecentRegistrations = async () => {
    return get('/api/recent-time-registrations');
  };

  const checkClockInStatus = async () => {
    return get('/api/check-clock-in-status');
  };

  const clockIn = async (payload = {}) => {
    return post('/api/time-registrations/clock-in', payload, {
      showSuccessNotification: true,
      successMessage: 'Successfully clocked in'
    });
  };

  const clockOut = async (payload = {}) => {
    return post('/api/time-registrations/clock-out', payload, {
      showSuccessNotification: true,
      successMessage: 'Successfully clocked out'
    });
  };

  const updateTimeRegistration = async (id, payload) => {
    // Ensure payload is structured as expected by the backend
    // The component was sending clock_in: "HH:MM:SS" and clock_out: "HH:MM:SS" or null
    const formattedPayload = { ...payload };
    if (formattedPayload.clock_in && formattedPayload.clock_in.length === 5) {
        formattedPayload.clock_in += ':00';
    }
    if (formattedPayload.clock_out && formattedPayload.clock_out.length === 5) {
        formattedPayload.clock_out += ':00';
    } else if (formattedPayload.clock_out === '') {
        formattedPayload.clock_out = null; // Handle empty string as null
    }

    return put(`/api/time-registrations/${id}`, formattedPayload, {
      showSuccessNotification: true,
      successMessage: 'Time registration updated successfully'
    });
  };

  const calculateStatistics = (registrations) => {
    if (!registrations || !registrations.length) {
      return { weeklyHours: 0, monthlyHours: 0 };
    }

    const now = new Date();
    const startOfWeek = new Date(now);
    // Assuming Sunday is the first day of the week (0 for Sunday, 1 for Monday, etc.)
    startOfWeek.setDate(now.getDate() - now.getDay());
    startOfWeek.setHours(0, 0, 0, 0);

    const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
    startOfMonth.setHours(0,0,0,0);

    let weeklyHours = 0;
    let monthlyHours = 0;

    registrations.forEach(reg => {
      try {
        // Ensure date is valid, and total_hours is a number
        const regDate = new Date(reg.date);
        const hours = parseFloat(reg.total_hours || 0);

        if (isNaN(regDate.getTime())) {
            console.warn('Invalid date found in registration:', reg);
            return;
        }

        if (regDate >= startOfMonth) {
          monthlyHours += hours;
        }

        if (regDate >= startOfWeek) {
          weeklyHours += hours;
        }
      } catch (e) {
        console.error('Error processing registration for statistics:', reg, e);
      }
    });

    return {
      weeklyHours: Math.round(weeklyHours * 10) / 10,
      monthlyHours: Math.round(monthlyHours * 10) / 10
    };
  };

  return {
    loading,
    snackbar,
    fetchRecentRegistrations,
    checkClockInStatus,
    clockIn,
    clockOut,
    updateTimeRegistration,
    calculateStatistics
  };
}
