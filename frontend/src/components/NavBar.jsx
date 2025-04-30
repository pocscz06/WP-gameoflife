import React, { useState, useEffect } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import "../styles/NavBar.css";

const NavBar = () => {
  const [user, setUser] = useState(null);
  const [click, setClick] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();

  const handleClick = () => setClick(!click);
  const closeMobileMenu = () => setClick(false);

  useEffect(() => {
    const checkLoginStatus = async () => {
      try {
        const response = await fetch(
          "https://codd.cs.gsu.edu/~kpham21/WP-gameoflife/backend/api/user/check-session.php",
          {
            credentials: "include",
          }
        );
        const data = await response.json();

        if (data.logged_in) {
          setUser(data.user);
        } else {
          setUser(null);
        }
      } catch (error) {
        console.error("Error checking login status:", error);
        setUser(null);
      }
    };

    checkLoginStatus();

    const handleStorageChange = (e) => {
      if (e.key === "auth_change") {
        checkLoginStatus();
      }
    };

    window.addEventListener("storage", handleStorageChange);

    return () => {
      window.removeEventListener("storage", handleStorageChange);
    };
  }, [location.pathname]);

  const handleLogout = async () => {
    try {
      const response = await fetch(
        "https://codd.cs.gsu.edu/~kpham21/WP-gameoflife/backend/auth/logout.php",
        {
          method: "POST",
          credentials: "include",
        }
      );

      if (response.ok) {
        setUser(null);
        navigate("/login");
      } else {
        console.error("Logout failed with status:", response.status);
      }
    } catch (error) {
      console.error("Error logging out:", error);
    }
  };

  return (
    <nav className="navbar">
      <div className="navbar-container">
        <Link to="/" className="navbar-logo" onClick={closeMobileMenu}>
          Game of Life
        </Link>

        <div className="menu-icon" onClick={handleClick}>
          <i className={click ? "fas fa-times" : "fas fa-bars"} />
        </div>

        <ul className={click ? "nav-menu active" : "nav-menu"}>
          <li className="nav-item">
            <Link to="/" className="nav-link" onClick={closeMobileMenu}>
              Home
            </Link>
          </li>

          <li className="nav-item">
            <Link to="/game" className="nav-link" onClick={closeMobileMenu}>
              Play Game
            </Link>
          </li>

          {user ? (
            <>
              {user.is_admin === 1 && (
                <li className="nav-item">
                  <Link
                    to="/admin/dashboard"
                    className="nav-link"
                    onClick={closeMobileMenu}
                  >
                    Dashboard
                  </Link>
                </li>
              )}
              <li className="nav-item">
                <button className="nav-button" onClick={handleLogout}>
                  Logout
                </button>
              </li>
            </>
          ) : (
            <>
              <li className="nav-item">
                <Link
                  to="/login"
                  className="nav-link"
                  onClick={closeMobileMenu}
                >
                  Login
                </Link>
              </li>
              <li className="nav-item">
                <Link
                  to="/register"
                  className="nav-link"
                  onClick={closeMobileMenu}
                >
                  Register
                </Link>
              </li>
            </>
          )}
        </ul>
      </div>
    </nav>
  );
};

export default NavBar;
