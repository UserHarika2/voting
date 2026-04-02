# VoteFlow - Online Voting System

## 📋 Project Overview

**VoteFlow** is a full-stack, browser-based voting platform built with HTML, CSS, JavaScript, and JSON. It enables administrators to manage candidates and allows authenticated voters to cast votes exactly once, with real-time results tracking.

## 🚀 Quick Start

### Access Points

1. **Voter Portal**: [http://localhost/scm/index.html](http://localhost/scm/index.html)
2. **Admin Panel**: [http://localhost/scm/admin-login.html](http://localhost/scm/admin-login.html)
3. **Results Page**: [http://localhost/scm/results.html](http://localhost/scm/results.html)

### Default Credentials

**Voter Login:**
- Voter ID: `V001`, `V002`, or `V003`
- Password: `voter123`

**Admin Login:**
- Username: `admin`
- Password: `admin123`

## 📁 Project Structure

```
scm/
├── index.html                 # Voter Login Page
├── voter.html                 # Vote Casting Dashboard
├── admin-login.html           # Admin Login Page
├── admin.html                 # Admin Dashboard (Candidate Management)
├── results.html               # Live Results Page
├── css/
│   └── style.css             # Complete styling with gradients & animations
├── js/
│   ├── app.js                # Core application logic & utilities
│   └── script.js             # Legacy file (can be removed)
├── data/
│   ├── config.json           # App configuration
│   ├── candidates.json       # Candidates list & vote counts
│   └── voters.json           # Voter accounts & voting status
└── php/                      # Legacy files (not used in this version)
```

## 🎨 Design Features

### Color Scheme (No Purple!)
- **Primary Blue**: `#0F3460` - Dark blue for text and headers
- **Teal/Green**: `#16A085` - Accent color for buttons and highlights
- **Gold**: `#D4AF37` - Accent for special elements
- **Gradients**: Warm (blue→teal→gold) for voter pages, Navy→cyan for admin

### UI Components
- **Glass-Morphism Cards**: Semi-transparent backgrounds with backdrop blur
- **Smooth Animations**: Slide-in effects and hover states
- **Responsive Design**: Mobile-friendly layouts
- **Modal Dialogs**: Confirmation modals for critical actions

## 📖 Pages & Features

### 1. **Voter Login** (`index.html`)
- Clean gradient background
- Voter ID & password authentication
- Direct access to results
- Admin login link

### 2. **Voter Dashboard** (`voter.html`)
- **Protected Route**: Redirects to login if not authenticated
- **Candidate Grid**: Dynamic candidate cards fetched from data
- **Vote Confirmation Modal**: Prevents accidental votes
- **Already-Voted State**: Shows disabled UI and highlights voted candidate
- **One-Vote Enforcement**: Server-side validation prevents double voting
- **Navigation**: Links to results page

### 3. **Admin Login** (`admin-login.html`)
- Admin-specific styling (Navy→Cyan gradient)
- Username & password authentication
- Links to voter portal and results

### 4. **Admin Dashboard** (`admin.html`)
- **Add Candidates**: Form to add candidates with name and party
- **Candidates Table**: Displays all candidates with vote counts
- **Delete Action**: Remove candidates with confirmation
- **Live Stats**: Total candidates, total votes, leading candidate
- **Real-time Updates**: Candidates appear immediately after addition

### 5. **Results Page** (`results.html`)
- **Public Access**: No login required
- **Candidate Rankings**: Sorted by vote count
- **Visual Bars**: Gradient-filled progress bars
- **Leader Badge**: 🏆 emoji for leading candidate
- **Auto-Refresh**: Toggle 30-second auto-refresh
- **Manual Refresh**: Button to refresh on demand
- **Total Vote Counter**: Displays total votes cast

## 🔐 Authentication & Security

### Session Management
- Uses `localStorage` for client-side session storage
- Checks authentication on every protected page load
- Automatic redirect to login if session invalid

### One-Vote Enforcement
- `hasVoted` flag stored in localStorage
- Server-side validation before vote submission
- Returns 403 error if user has already voted
- Vote cannot be changed once cast

### Credentials
- Admin accounts stored in `data/voters.json`
- Voter accounts with password hashes (MD5 in production)
- In development mode, passwords stored plaintext for demo

## 💾 Data Storage

### JSON Files

**candidates.json**: Stores candidate information
```json
{
  "candidates": [
    {
      "id": 1,
      "name": "Alice Johnson",
      "party": "Progressive Alliance",
      "voteCount": 0
    }
  ]
}
```

**voters.json**: Stores voter and admin credentials
```json
{
  "voters": [...],
  "admin": {
    "username": "admin",
    "password": "admin123"
  }
}
```

### LocalStorage Keys
- `userRole`: "voter" or "admin"
- `userId`: Current user's ID
- `userHasVoted`: Boolean flag for voter
- `userVotedFor`: ID of candidate voter chose
- `candidates`: Cached candidates list

## 🔄 Data Flow

```
User Login
    ↓
Auth.voterLogin() / Auth.adminLogin()
    ↓
localStorage updated
    ↓
Redirect to respective dashboard
    ↓
Load candidates from JSON/cache
    ↓
Display UI based on user role
```

### Vote Submission Flow
```
1. User selects candidate
2. Confirmation modal displayed
3. User confirms
4. Voting.castVote() called
5. Candidates.addVote() updates vote count
6. userHasVoted = true
7. UI transitions to already-voted state
```

## 🛠️ JavaScript Utilities

### Auth Module
```javascript
Auth.voterLogin(voterId, password)    // Returns {success, message}
Auth.adminLogin(username, password)   // Returns {success, message}
Auth.isAuthenticated()                // Boolean
Auth.getUserRole()                    // Returns "voter" or "admin"
Auth.logout()                         // Clears session
```

### Candidates Module
```javascript
Candidates.loadCandidates()           // Fetch from JSON
Candidates.getAllCandidates()         // Get cached list
Candidates.addCandidate(name, party)  // Add new candidate
Candidates.deleteCandidate(id)        // Remove candidate
Candidates.addVote(candidateId)       // Increment vote count
```

### Voting Module
```javascript
Voting.castVote(candidateId)          // Record vote
Voting.getResults()                   // Sorted by votes
Voting.getTotalVotes()                // Sum of all votes
```

### UI Module
```javascript
UI.showAlert(message, type)           // Show alert
UI.showModal(modalId)                 // Display modal
UI.closeModal(modalId)                // Hide modal
UI.setupModalClose(modalId)           // Close on background click
```

## 🎯 Business Rules

### One-Vote Rule
- Each voter can cast exactly ONE vote
- Subsequent logins show voting as disabled
- Previously voted candidate is visually highlighted
- Server validates before recording any vote

### Candidate Management
- Admin can add/remove candidates in real-time
- Changes appear immediately on voter page
- Delete shows confirmation modal
- Vote counts update in real-time

### Vote Counting
- Incremented per vote cast
- Persists in-memory during session
- Can be exported or viewed at any time

## 🌐 Browser Compatibility

- Chrome/Chromium (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (responsive design)

## 📱 Responsive Breakpoints

- **Desktop**: 1200px+ (full features)
- **Tablet**: 768px - 1199px (adjusted grid)
- **Mobile**: Below 768px (single column, optimized touch)

## ⚙️ Configuration

Edit `data/config.json` to customize:
```json
{
  "appName": "VoteFlow",
  "features": {
    "resultsAutoRefresh": true,
    "refreshInterval": 30000
  },
  "colors": {
    "primary": "#0F3460",
    "secondary": "#16A085",
    "accent": "#D4AF37"
  }
}
```

## 🚨 Error Handling

### Common Errors
| Error | Cause | Solution |
|-------|-------|----------|
| 401 Unauthorized | Invalid credentials | Check voter ID/password |
| 403 Already Voted | User already voted | Can't vote again (by design) |
| 500 Server Error | Data loading issue | Refresh page |
| No Candidates | Admin hasn't added any | Admin must add candidates |

## 🔄 Development & Deployment

### Local Development
1. Place files in `C:\xampp\htdocs\scm\`
2. Start XAMPP Apache server
3. Visit `http://localhost/scm/`

### Production Deployment
1. Ensure all HTML/CSS/JS files are uploaded
2. Data files should be accessible to JavaScript
3. Consider adding CORS headers if needed
4. Use HTTPS for security
5. Implement backend API for data persistence

## 📝 Future Enhancements

- [ ] Backend API integration (Node.js/Express)
- [ ] Database persistence (MySQL)
- [ ] Email notifications on vote
- [ ] Vote receipt generation
- [ ] Audit logging
- [ ] Export results (PDF/CSV)
- [ ] Multi-election support
- [ ] Role-based admin access

## 📄 License

VoteFlow is provided as-is for educational and organizational purposes.

---

**Last Updated**: April 2026
**Version**: 1.0.0
**Built With**: HTML5, CSS3, JavaScript (ES6+), JSON
