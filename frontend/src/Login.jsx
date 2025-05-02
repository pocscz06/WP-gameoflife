import React, { useState, useEffect } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import "./styles/Auth.css";

const Login = () => {
  const navigate = useNavigate();
  const location = useLocation();

  const [formData, setFormData] = useState({
    username: "",
    password: "",
  });

  const [errors, setErrors] = useState([]);
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    const checkLoginStatus = async () => {
      try {
        const response = await fetch(
          "/api/WP-gameoflife/backend/api/user/check-session.php"
        );
        const data = await response.json();

        if (data.logged_in) {
          if (data.user.is_admin) {
            navigate("/admin/dashboard");
          } else {
            navigate("/game");
          }
        }
      } catch (error) {
        console.error("Error checking login status:", error);
      }
    };

    checkLoginStatus();
  }, [navigate]);

  useEffect(() => {
    const params = new URLSearchParams(location.search);
    const registered = params.get("registered");

    if (registered === "true") {
      setErrors([]);
    }
  }, [location]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData({
      ...formData,
      [name]: value,
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!formData.username.trim() || !formData.password) {
      setErrors(["Username and password are required"]);
      return;
    }

    setIsSubmitting(true);

    try {
      const response = await fetch(
        "/api/WP-gameoflife/backend/auth/login.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            username: formData.username,
            password: formData.password,
          }),
        }
      );

      const data = await response.json();

      if (data.success) {
        localStorage.setItem("auth_change", Date.now().toString());

        if (data.user.is_admin) {
          navigate("/admin/dashboard");
        } else {
          navigate("/game");
        }
      } else {
        setErrors(data.errors || ["Invalid username or password"]);
      }
    } catch (error) {
      console.error("Login error:", error);
      setErrors(["An error occurred. Please try again later."]);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="auth-container">
      <div className="auth-card">
        <h2>Login</h2>

        {errors.length > 0 && (
          <div className="error-box">
            <ul>
              {errors.map((error, index) => (
                <li key={index}>{error}</li>
              ))}
            </ul>
          </div>
        )}

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label htmlFor="username">Username</label>
            <input
              type="text"
              id="username"
              name="username"
              value={formData.username}
              onChange={handleChange}
              required
            />
          </div>

          <div className="form-group">
            <label htmlFor="password">Password</label>
            <input
              type="password"
              id="password"
              name="password"
              value={formData.password}
              onChange={handleChange}
              required
            />
          </div>

          <button type="submit" className="auth-button" disabled={isSubmitting}>
            {isSubmitting ? "Logging in..." : "Login"}
          </button>
        </form>

        <div className="auth-footer">
          Don't have an account? <Link to="/register">Register</Link>
        </div>
      </div>
    </div>
  );
};

export default Login;
