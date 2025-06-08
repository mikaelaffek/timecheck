// Format time (HH:MM:SS to HH:MM)
export const formatTime = (time) => {
  if (!time) return '-'
  // If already in HH:MM format, return as is
  if (time.length === 5) return time
  // Otherwise parse and format
  try {
    const date = new Date(`2000-01-01T${time}`)
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  } catch (e) {
    console.error('Error formatting time:', e)
    return time.substring(0, 5)
  }
}

// Format date (YYYY-MM-DD to DD/MM/YYYY)
export const formatDate = (dateString) => {
  if (!dateString) return '-'
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString()
  } catch (e) {
    console.error('Error formatting date:', e)
    return dateString
  }
}

// Get status color
export const getStatusColor = (status) => {
  switch (status) {
    case 'approved': return 'success'
    case 'rejected': return 'error'
    case 'pending': return 'warning'
    default: return 'grey'
  }
}

// Format hours and minutes
export const formatHoursAndMinutes = (totalHours) => {
  if (!totalHours) return '-'

  const hours = Math.floor(totalHours)
  const minutes = Math.round((totalHours - hours) * 60)

  return `${hours}h ${minutes}m`
}

// Get avatar URL for user
export const getAvatarUrl = (userId) => {
  return `https://i.pravatar.cc/150?u=${userId}`
}
