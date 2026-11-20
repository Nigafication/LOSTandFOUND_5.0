"use client";

import { FormEvent, useEffect, useMemo, useState } from "react";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000";

function xsrfToken() {
  const cookie = document.cookie.split("; ").find((part) => part.startsWith("XSRF-TOKEN="));
  return cookie ? decodeURIComponent(cookie.substring("XSRF-TOKEN=".length)) : "";
}

async function sanctumFetch(path: string, init: RequestInit = {}) {
  const headers = new Headers(init.headers);
  headers.set("Accept", "application/json");
  headers.set("X-Requested-With", "XMLHttpRequest");
  const token = xsrfToken();
  if (token) headers.set("X-XSRF-TOKEN", token);
  return fetch(`${API_URL}${path}`, { ...init, headers, credentials: "include" });
}

type Item = {
  id: number;
  name: string;
  category: string;
  location: string;
  date: string;
  age: string;
  status: "Found" | "Recently found";
  description: string;
  tone: string;
  icon: string;
  saved?: boolean;
};

const fallbackItems: Item[] = [
  { id: 1, name: "Silver MacBook charger", category: "Electronics", location: "Powell Library · 2nd floor", date: "Today, 9:42 AM", age: "12 min ago", status: "Recently found", description: "USB-C power adapter with a small blue sticker on the side.", tone: "blue", icon: "⌁" },
  { id: 2, name: "Black canvas tote bag", category: "Bags", location: "Kerckhoff Coffee House", date: "Today, 8:10 AM", age: "2 hrs ago", status: "Found", description: "Has a yellow paperback, a water bottle, and a set of keys inside.", tone: "gold", icon: "▱" },
  { id: 3, name: "Gold hoop earring", category: "Jewelry", location: "Royce Hall · North steps", date: "Yesterday, 4:26 PM", age: "18 hrs ago", status: "Found", description: "Single small hoop with a tiny textured detail around the edge.", tone: "rose", icon: "◌" },
  { id: 4, name: "Blue Hydro Flask", category: "Drinkware", location: "John Wooden Center", date: "Yesterday, 1:03 PM", age: "1 day ago", status: "Found", description: "24 oz bottle with a white mountain decal and a few scuffs.", tone: "mint", icon: "◒" },
  { id: 5, name: "Student ID card", category: "Cards & IDs", location: "Sunset Village", date: "Mon, 11:18 AM", age: "2 days ago", status: "Found", description: "Name is visible. The card is being held securely at the front desk.", tone: "ink", icon: "▣" },
  { id: 6, name: "Green knit scarf", category: "Clothing", location: "Bunche Hall · Room 315", date: "Sun, 3:51 PM", age: "3 days ago", status: "Found", description: "Forest green scarf with a soft ribbed texture and fringe ends.", tone: "green", icon: "≋" },
];

const categories = ["All items", "Electronics", "Bags", "Cards & IDs", "Clothing", "Drinkware", "Jewelry"];

function Mark() {
  return <span className="mark" aria-hidden="true"><span /></span>;
}

function Arrow() {
  return <span aria-hidden="true" className="arrow">↗</span>;
}

export default function Home() {
  const [items, setItems] = useState<Item[]>(fallbackItems);
  const [query, setQuery] = useState("");
  const [category, setCategory] = useState("All items");
  const [saved, setSaved] = useState<number[]>([]);
  const [showReport, setShowReport] = useState(false);
  const [submitted, setSubmitted] = useState(false);
  const [showLogin, setShowLogin] = useState(false);
  const [user, setUser] = useState<string | null>(null);
  const [loginError, setLoginError] = useState("");
  const [loggingIn, setLoggingIn] = useState(false);
  const [loadingItems, setLoadingItems] = useState(true);

  useEffect(() => {
    const loadSessionAndItems = async () => {
      try {
        const [itemsResponse, userResponse] = await Promise.all([
          fetch(`${API_URL}/api/items`, { credentials: "include" }),
          sanctumFetch("/api/user"),
        ]);
        if (itemsResponse.ok) {
          const payload = await itemsResponse.json();
          if (Array.isArray(payload.data)) {
            setItems(payload.data);
            setSaved(payload.data.filter((item: Item) => item.saved).map((item: Item) => item.id));
          }
        }
        if (userResponse.ok) {
          const payload = await userResponse.json();
          setUser(payload.user?.name ?? null);
        }
      } finally {
        setLoadingItems(false);
      }
    };
    void loadSessionAndItems();
  }, []);

  const filteredItems = useMemo(() => {
    const normalized = query.toLowerCase().trim();
    return items.filter((item) => {
      const matchesCategory = category === "All items" || item.category === category;
      const matchesQuery = !normalized || [item.name, item.category, item.location, item.description].some((value) => String(value ?? "").toLowerCase().includes(normalized));
      return matchesCategory && matchesQuery;
    });
  }, [category, items, query]);

  const toggleSaved = async (id: number) => {
    if (!user) {
      setShowLogin(true);
      return;
    }
    const isSaved = saved.includes(id);
    setSaved((current) => isSaved ? current.filter((itemId) => itemId !== id) : [...current, id]);
    await sanctumFetch(`/api/items/${id}/save`, { method: isSaved ? "DELETE" : "POST" });
  };

  const handleReport = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const response = await sanctumFetch("/api/items", {
      method: "POST",
      body: data,
    });
    if (response.ok) {
      const payload = await response.json();
      setItems((current) => [{
        id: payload.item.id,
        name: payload.item.title,
        category: payload.item.category,
        location: payload.item.location,
        date: payload.item.created_at ? new Date(payload.item.created_at).toLocaleDateString() : "Just now",
        age: "Just now",
        status: "Recently found",
        description: payload.item.description,
        tone: payload.item.tone ?? "blue",
        icon: payload.item.icon ?? "◌",
      }, ...current]);
      setSubmitted(true);
    }
  };

  const openReport = () => user ? setShowReport(true) : setShowLogin(true);

  const handleLogin = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setLoggingIn(true);
    setLoginError("");
    const form = new FormData(event.currentTarget);
    const email = String(form.get("email") ?? "");
    const password = String(form.get("password") ?? "");
    try {
      await fetch(`${API_URL}/sanctum/csrf-cookie`, { credentials: "include" });
      const response = await sanctumFetch("/api/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password }),
      });
      if (!response.ok) throw new Error("Those credentials do not match our records.");
      const data = await response.json();
      setUser(data.user?.name ?? email.split("@")[0]);
      setShowLogin(false);
    } catch {
      setLoginError("We couldn't sign you in. Check your email and password, then try again.");
    } finally {
      setLoggingIn(false);
    }
  };

  return (
    <main>
      <div className="notice"><span>Campus-wide service</span><span>Lost something? Start here. <a href="#browse">Browse found items <Arrow /></a></span></div>
      <header className="site-header">
        <a className="brand" href="#top" aria-label="Campus Lost and Found home"><Mark /><span>campus<br /><strong>lost & found</strong></span></a>
        <nav className="desktop-nav" aria-label="Main navigation"><a href="#browse">Browse items</a><a href="#how-it-works">How it works</a><a href="#faq">FAQ</a></nav>
        <div className="header-actions"><button className="saved-link" onClick={() => setQuery("saved")}><span className="saved-dot">{saved.length}</span> Saved items</button>{user ? <span className="user-pill">Hi, {user}</span> : <button className="sign-in-link" onClick={() => setShowLogin(true)}>Sign in</button>}<button className="button button-dark button-small" onClick={openReport}>Report an item <Arrow /></button></div>
      </header>

      <section className="hero" id="top">
        <div className="hero-copy"><p className="eyebrow"><span className="eyebrow-line" /> UCLALF · EST. 2024</p><h1>Your missing item<br /><em>might be here.</em></h1><p className="hero-lede">A simple way to search, share, and reunite with things lost around campus.</p><div className="hero-actions"><a className="button button-primary" href="#browse">Browse found items <Arrow /></a><button className="text-button" onClick={openReport}>I found something <Arrow /></button></div></div>
        <div className="hero-art" aria-hidden="true"><div className="art-label">FOUND / 06.24</div><div className="art-circle"><span className="circle-inner" /></div><div className="art-note"><strong>Maybe it&apos;s<br />closer than<br />you think.</strong><span>↘</span></div><div className="art-sticker">KEEP<br />LOOKING</div></div>
      </section>

      <section className="stats-strip"><div><strong>146</strong><span>items reunited</span></div><div><strong>24h</strong><span>average response</span></div><div><strong>90 days</strong><span>items held securely</span></div><p>Managed by the campus community<br />with care and a little persistence.</p></section>

      <section className="browse-section" id="browse"><div className="section-heading"><div><p className="eyebrow"><span className="eyebrow-line" /> LIVE BOARD</p><h2>Recently found</h2></div><p className="section-intro">Search the board before you retrace your steps. New finds are added every day.</p></div>
        <div className="search-row"><label className="search-box"><span>⌕</span><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search by item, place, or keyword" aria-label="Search found items" />{query && <button onClick={() => setQuery("")} aria-label="Clear search">×</button>}</label><div className="results-count"><strong>{filteredItems.length.toString().padStart(2, "0")}</strong> items on the board</div></div>
        <div className="filter-row" role="tablist" aria-label="Filter by category">{categories.map((itemCategory) => <button key={itemCategory} className={category === itemCategory ? "filter active" : "filter"} onClick={() => setCategory(itemCategory)}>{itemCategory}</button>)}</div>
        {loadingItems ? <div className="empty-state"><span>⌁</span><h3>Loading the live board…</h3><p>Checking the campus database for recent reports.</p></div> : filteredItems.length > 0 ? <div className="item-grid">{filteredItems.map((item) => <article className="item-card" key={item.id}><div className={`item-visual ${item.tone}`}><span>{item.icon}</span><button className={saved.includes(item.id) ? "save-button saved" : "save-button"} onClick={() => toggleSaved(item.id)} aria-label={saved.includes(item.id) ? `Remove ${item.name} from saved items` : `Save ${item.name}`} aria-pressed={saved.includes(item.id)}>{saved.includes(item.id) ? "♥" : "♡"}</button><span className="visual-label">{item.category}</span></div><div className="item-content"><div className="item-topline"><span className="status"><i /> {item.status}</span><span>{item.age}</span></div><h3>{item.name}</h3><p>{item.description}</p><div className="item-meta"><span>⌖ {item.location}</span><span>{item.date}</span></div></div></article>)}</div> : <div className="empty-state"><span>⌕</span><h3>No items match that search.</h3><p>Try a different keyword or clear the category filter.</p><button className="text-button" onClick={() => { setQuery(""); setCategory("All items"); }}>Clear all filters <Arrow /></button></div>}
        <div className="board-footer"><span>Showing {filteredItems.length} of {items.length} found items</span><button className="text-button">View all items <Arrow /></button></div>
      </section>

      <section className="how-section" id="how-it-works"><div className="how-heading"><p className="eyebrow"><span className="eyebrow-line" /> GOOD TO KNOW</p><h2>Small steps.<br /><em>Big relief.</em></h2></div><div className="steps"><div className="step"><span>01</span><h3>Search the board</h3><p>Start with what you know: the item, the place, or the day it went missing.</p></div><div className="step"><span>02</span><h3>Spot your item</h3><p>Check the details and save a match so you can come back to it anytime.</p></div><div className="step"><span>03</span><h3>Reach out</h3><p>Tell us a little more and our team will help verify and arrange a safe pickup.</p></div></div></section>

      <section className="notice-section" id="faq"><div className="notice-icon">!</div><div><p className="eyebrow">OFFICIAL NOTICE</p><h2>Search first. File a report when you need to.</h2><p>Campus Lost & Found is a search mechanism for property that has been turned in. It is not an official police report. Items are held for up to 90 days, after which unclaimed property may be donated, auctioned, or discarded.</p></div><button className="button button-light" onClick={openReport}>Report an item <Arrow /></button></section>

      <footer><a className="brand footer-brand" href="#top"><Mark /><span>campus<br /><strong>lost & found</strong></span></a><p>Made for the things that matter.<br />© 2024 Campus Lost & Found</p><div className="footer-links"><a href="#how-it-works">How it works</a><a href="#faq">Privacy & safety</a><a href="mailto:lostandfound@example.edu">Contact</a></div></footer>

      {showLogin && <div className="modal-backdrop" role="presentation" onMouseDown={(event) => event.target === event.currentTarget && setShowLogin(false)}><div className="modal" role="dialog" aria-modal="true" aria-labelledby="login-title"><button className="modal-close" onClick={() => setShowLogin(false)} aria-label="Close login form">×</button><p className="eyebrow">WELCOME BACK</p><h2 id="login-title">Sign in</h2><p className="modal-lede">Sign in to save items, report finds, and keep track of your requests.</p><form onSubmit={handleLogin}><label>Email address<input name="email" type="email" required placeholder="you@campus.edu" /></label><label>Password<input name="password" type="password" required placeholder="Enter your password" /></label>{loginError && <p className="form-error">{loginError}</p>}<button className="button button-primary form-submit" type="submit" disabled={loggingIn}>{loggingIn ? "Signing in…" : <>Sign in <Arrow /></>}</button></form><p className="modal-footnote">New here? Registration is available through the campus account portal.</p></div></div>}

      {showReport && <div className="modal-backdrop" role="presentation" onMouseDown={(event) => event.target === event.currentTarget && setShowReport(false)}><div className="modal" role="dialog" aria-modal="true" aria-labelledby="report-title"><button className="modal-close" onClick={() => setShowReport(false)} aria-label="Close report form">×</button>{submitted ? <div className="success-state"><div className="success-mark">✓</div><p className="eyebrow">THANK YOU</p><h2>Your report is in.</h2><p>We&apos;ll review the details and add your item to the board when it&apos;s ready.</p><button className="button button-primary" onClick={() => { setSubmitted(false); setShowReport(false); }}>Back to the board <Arrow /></button></div> : <><p className="eyebrow">HELP IT FIND A HOME</p><h2 id="report-title">Report an item</h2><p className="modal-lede">Found something around campus? Give us the details and we&apos;ll take it from there.</p><form onSubmit={handleReport}><label>What did you find?<input name="title" required placeholder="Item name" /></label><label>Item type<select name="kind" required defaultValue="found"><option value="found">I found something</option><option value="lost">I lost something</option></select></label><label>Category<select name="category" required defaultValue=""><option value="" disabled>Select a category</option>{categories.slice(1).map((itemCategory) => <option key={itemCategory}>{itemCategory}</option>)}</select></label><label>Tell us about it<textarea name="description" required placeholder="Color, brand, distinctive details…" rows={4} /></label><label>Where did you find it?<input name="location" required placeholder="Building, room, or landmark" /></label><button className="button button-primary form-submit" type="submit">Submit report <Arrow /></button></form></>}</div></div>}
    </main>
  );
}
