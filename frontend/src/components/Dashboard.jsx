import React, { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import "../styles/Admin.css";

const AdminDashboard = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState("users");
  const [users, setUsers] = useState([]);
  const [sessions, setSessions] = useState([]);
  const [stats, setStats] = useState({
    totalUsers: 0,
    totalSessions: 0,
    averageGenerations: 0,
  });

  useEffect(() => {
    const checkAdminStatus = async () => {
      try {
        const response = await fetch(
          "https://codd.cs.gsu.edu/~kpham21/WP-gameoflife/backend/api/user/check-session.php",
          {
            credentials: "include",
          }
        );
        const data = await response.json();

        if (!data.logged_in || !data.user.is_admin) {
          navigate("/login");
        } else {
          fetchAdminData();
        }
      } catch (error) {
        console.error("Error checking admin status:", error);
        navigate("/login");
      }
    };

    checkAdminStatus();
  }, [navigate]);

  const fetchAdminData = async () => {
    setLoading(true);

    try {
      const usersResponse = await fetch(
        "https://codd.cs.gsu.edu/~kpham21/WP-gameoflife/backend/admin/api/users.php",
        {
          credentials: "include",
        }
      );
      const usersData = await usersResponse.json();

      if (usersData.success) {
        setUsers(usersData.users);
      }

      const sessionsResponse = await fetch(
        "https://codd.cs.gsu.edu/~kpham21/WP-gameoflife/backend/admin/api/sessions.php",
        {
          credentials: "include",
        }
      );
      const sessionsData = await sessionsResponse.json();

      if (sessionsData.success) {
        setSessions(sessionsData.sessions);
      }

      const statsResponse = await fetch(
        "https://codd.cs.gsu.edu/~kpham21/WP-gameoflife/backend/admin/api/stats.php",
        {
          credentials: "include",
        }
      );
      const statsData = await statsResponse.json();

      if (statsData.success) {
        setStats(statsData.stats);
      }

      setLoading(false);
    } catch (error) {
      console.error("Error fetching admin data:", error);
      setLoading(false);
    }
  };

  const handleDeleteUser = async (userId) => {
    if (
      !window.confirm(
        "Are you sure you want to delete this user? This action cannot be undone."
      )
    ) {
      return;
    }

    try {
      const response = await fetch(
        `https://codd.cs.gsu.edu/~kpham21/WP-gameoflife/backend/admin/api/users.php?id=${userId}`,
        {
          method: "DELETE",
          credentials: "include",
        }
      );

      const data = await response.json();

      if (data.success) {
        setUsers(users.filter((user) => user.user_id !== userId));
        alert("User deleted successfully");
      } else {
        alert("Failed to delete user: " + data.message);
      }
    } catch (error) {
      console.error("Error deleting user:", error);
      alert("An error occurred while deleting user");
    }
  };

  const handlePromoteUser = async (userId) => {
    try {
      const response = await fetch(
        `https://codd.cs.gsu.edu/~kpham21/WP-gameoflife/backend/admin/api/users.php?id=${userId}`,
        {
          method: "PUT",
          credentials: "include",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ is_admin: true }),
        }
      );

      const data = await response.json();

      if (data.success) {
        setUsers(
          users.map((user) =>
            user.user_id === userId ? { ...user, is_admin: true } : user
          )
        );
        alert("User promoted to admin successfully");
      } else {
        alert("Failed to promote user: " + data.message);
      }
    } catch (error) {
      console.error("Error promoting user:", error);
      alert("An error occurred while promoting user");
    }
  };

  const renderUsersTab = () => {
    if (users.length === 0) {
      return <p className="no-data">No users found</p>;
    }

    return (
      <div className="data-table">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Username</th>
              <th>Email</th>
              <th>Created</th>
              <th>Role</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {users.map((user) => (
              <tr key={user.user_id}>
                <td>{user.user_id}</td>
                <td>{user.username}</td>
                <td>{user.email}</td>
                <td>{new Date(user.created_at).toLocaleString()}</td>
                <td>{user.is_admin ? "Admin" : "User"}</td>
                <td className="actions">
                  {!user.is_admin && (
                    <button
                      className="action-btn promote"
                      onClick={() => handlePromoteUser(user.user_id)}
                    >
                      Promote
                    </button>
                  )}
                  <button
                    className="action-btn delete"
                    onClick={() => handleDeleteUser(user.user_id)}
                  >
                    Delete
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    );
  };

  const renderSessionsTab = () => {
    if (sessions.length === 0) {
      return <p className="no-data">No game sessions found</p>;
    }

    return (
      <div className="data-table">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Start Time</th>
              <th>End Time</th>
              <th>Generations</th>
              <th>Max Population</th>
              <th>Pattern</th>
            </tr>
          </thead>
          <tbody>
            {sessions.map((session) => (
              <tr key={session.session_id}>
                <td>{session.session_id}</td>
                <td>{session.username}</td>
                <td>{new Date(session.start_time).toLocaleString()}</td>
                <td>
                  {session.end_time
                    ? new Date(session.end_time).toLocaleString()
                    : "In Progress"}
                </td>
                <td>{session.generations_reached}</td>
                <td>{session.max_population}</td>
                <td>{session.pattern_used || "Custom"}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    );
  };

  const renderStatsTab = () => {
    return (
      <div className="stats-container">
        <div className="stat-box">
          <h3>Total Users</h3>
          <p className="stat-value">{stats.totalUsers}</p>
        </div>

        <div className="stat-box">
          <h3>Total Game Sessions</h3>
          <p className="stat-value">{stats.totalSessions}</p>
        </div>

        <div className="stat-box">
          <h3>Average Generations</h3>
          <p className="stat-value">{stats.averageGenerations.toFixed(2)}</p>
        </div>
      </div>
    );
  };

  return (
    <div className="admin-dashboard">
      <h1>Admin Dashboard</h1>

      <div className="tab-navigation">
        <button
          className={activeTab === "users" ? "active" : ""}
          onClick={() => setActiveTab("users")}
        >
          Users
        </button>
        <button
          className={activeTab === "sessions" ? "active" : ""}
          onClick={() => setActiveTab("sessions")}
        >
          Game Sessions
        </button>
        <button
          className={activeTab === "stats" ? "active" : ""}
          onClick={() => setActiveTab("stats")}
        >
          Statistics
        </button>
      </div>

      <div className="dashboard-content">
        {loading ? (
          <div className="loading">Loading dashboard data...</div>
        ) : (
          <>
            {activeTab === "users" && renderUsersTab()}
            {activeTab === "sessions" && renderSessionsTab()}
            {activeTab === "stats" && renderStatsTab()}
          </>
        )}
      </div>
    </div>
  );
};

export default AdminDashboard;
