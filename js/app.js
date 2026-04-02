// ===== VOTEFLOW APPLICATION UTILITIES =====

// Cache for candidates data
let cachedCandidates = null;
let resultRefreshInterval = null;

/**
 * AUTHENTICATION MANAGEMENT
 */

const Auth = {
  // Login for voter
  voterLogin: function(voterId, password) {
    // In production, this would call a backend API
    // For now, we'll simulate with localStorage and JSON data
    const mockVoters = {
      'V001': { password: 'voter123', hasVoted: false, votedFor: null },
      'V002': { password: 'voter123', hasVoted: false, votedFor: null },
      'V003': { password: 'voter123', hasVoted: false, votedFor: null }
    };

    if (mockVoters[voterId] && mockVoters[voterId].password === password) {
      localStorage.setItem('userRole', 'voter');
      localStorage.setItem('userId', voterId);
      localStorage.setItem('userHasVoted', mockVoters[voterId].hasVoted);
      localStorage.setItem('userVotedFor', mockVoters[voterId].votedFor);
      return { success: true };
    }
    return { success: false, message: 'Invalid credentials. Please try again.' };
  },

  // Login for admin
  adminLogin: function(username, password) {
    const adminCreds = { username: 'admin', password: 'admin123' };
    
    if (username === adminCreds.username && password === adminCreds.password) {
      localStorage.setItem('userRole', 'admin');
      localStorage.setItem('userId', 'admin');
      return { success: true };
    }
    return { success: false, message: 'Invalid admin credentials.' };
  },

  // Check if user is authenticated
  isAuthenticated: function() {
    return localStorage.getItem('userRole') !== null;
  },

  // Get current user role
  getUserRole: function() {
    return localStorage.getItem('userRole');
  },

  // Get current user ID
  getUserId: function() {
    return localStorage.getItem('userId');
  },

  // Check if voter has voted
  hasUserVoted: function() {
    const role = this.getUserRole();
    if (role !== 'voter') return false;
    return localStorage.getItem('userHasVoted') === 'true';
  },

  // Get candidate user voted for
  getUserVotedFor: function() {
    const voted = localStorage.getItem('userVotedFor');
    return voted ? parseInt(voted) : null;
  },

  // Logout
  logout: function() {
    localStorage.removeItem('userRole');
    localStorage.removeItem('userId');
    localStorage.removeItem('userHasVoted');
    localStorage.removeItem('userVotedFor');
  }
};

/**
 * CANDIDATE MANAGEMENT
 */

const Candidates = {
  // Load candidates from JSON file
  loadCandidates: async function() {
    if (cachedCandidates) {
      return cachedCandidates;
    }

    try {
      const response = await fetch('data/candidates.json');
      const data = await response.json();
      cachedCandidates = data.candidates;
      return cachedCandidates;
    } catch (error) {
      console.error('Error loading candidates:', error);
      return [];
    }
  },

  // Get all candidates
  getAllCandidates: function() {
    return cachedCandidates || [];
  },

  // Get candidate by ID
  getCandidateById: function(id) {
    const candidates = this.getAllCandidates();
    return candidates.find(c => c.id === id);
  },

  // Add candidate (admin)
  addCandidate: function(name, party) {
    const candidates = cachedCandidates || [];
    const newId = candidates.length > 0 ? Math.max(...candidates.map(c => c.id)) + 1 : 1;
    
    const newCandidate = {
      id: newId,
      name: name,
      party: party || 'Independent',
      voteCount: 0
    };

    candidates.push(newCandidate);
    cachedCandidates = candidates;
    localStorage.setItem('candidates', JSON.stringify(candidates));
    return newCandidate;
  },

  // Delete candidate (admin)
  deleteCandidate: function(id) {
    const candidates = cachedCandidates || [];
    const index = candidates.findIndex(c => c.id === id);
    
    if (index > -1) {
      candidates.splice(index, 1);
      cachedCandidates = candidates;
      localStorage.setItem('candidates', JSON.stringify(candidates));
      return true;
    }
    return false;
  },

  // Increment vote count
  addVote: function(candidateId) {
    const candidates = cachedCandidates || [];
    const candidate = candidates.find(c => c.id === candidateId);
    
    if (candidate) {
      candidate.voteCount += 1;
      cachedCandidates = candidates;
      localStorage.setItem('candidates', JSON.stringify(candidates));
      return true;
    }
    return false;
  }
};

/**
 * VOTING MANAGEMENT
 */

const Voting = {
  // Cast a vote
  castVote: function(candidateId) {
    const userId = Auth.getUserId();
    
    // Check if already voted
    if (Auth.hasUserVoted()) {
      return { success: false, message: 'You have already cast your vote.' };
    }

    // Increment vote count
    if (Candidates.addVote(candidateId)) {
      // Mark user as voted
      localStorage.setItem('userHasVoted', 'true');
      localStorage.setItem('userVotedFor', candidateId);
      return { success: true, message: 'Vote recorded successfully!' };
    }

    return { success: false, message: 'Error recording vote. Please try again.' };
  },

  // Get vote results
  getResults: async function() {
    await Candidates.loadCandidates();
    const candidates = Candidates.getAllCandidates();
    
    // Sort by vote count (descending)
    return candidates.sort((a, b) => b.voteCount - a.voteCount);
  },

  // Get total votes cast
  getTotalVotes: function() {
    const candidates = Candidates.getAllCandidates();
    return candidates.reduce((total, c) => total + c.voteCount, 0);
  }
};

/**
 * UI UTILITIES
 */

const UI = {
  // Show alert message
  showAlert: function(message, type = 'error') {
    const alert = document.getElementById('alert');
    if (!alert) return;

    alert.className = `alert alert-${type} show`;
    alert.textContent = message;
    
    setTimeout(() => {
      alert.classList.remove('show');
    }, 5000);
  },

  // Show modal
  showModal: function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = 'block';
    }
  },

  // Close modal
  closeModal: function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = 'none';
    }
  },

  // Close modal when clicking outside
  setupModalClose: function(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    window.addEventListener('click', (event) => {
      if (event.target === modal) {
        this.closeModal(modalId);
      }
    });
  },

  // Format candidate name for display
  formatCandidateName: function(name) {
    return name.charAt(0).toUpperCase() + name.slice(1);
  },

  // Get rank emoji
  getRankEmoji: function(rank) {
    switch(rank) {
      case 1: return '🥇';
      case 2: return '🥈';
      case 3: return '🥉';
      default: return '️️• ';
    }
  }
};

/**
 * VALIDATION UTILITIES
 */

const Validation = {
  // Validate voter ID
  isValidVoterId: function(voterId) {
    return voterId && voterId.length >= 3;
  },

  // Validate password
  isValidPassword: function(password) {
    return password && password.length >= 6;
  },

  // Validate candidate name
  isValidCandidateName: function(name) {
    return name && name.trim().length > 0;
  }
};

/**
 * Initialize app on page load
 */

document.addEventListener('DOMContentLoaded', async () => {
  // Check for cached candidates in localStorage
  const cachedData = localStorage.getItem('candidates');
  if (cachedData) {
    cachedCandidates = JSON.parse(cachedData);
  } else {
    await Candidates.loadCandidates();
  }
});

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { Auth, Candidates, Voting, UI, Validation };
}
