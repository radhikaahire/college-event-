<!DOCTYPE html>
<html>
<head>
<title>College Event Management</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{
  background:linear-gradient(135deg,#eef2ff,#f8fafc);
  font-family:'Segoe UI',sans-serif;
}

.navbar{
  background:white;
  border-bottom:1px solid #e5e7eb;
}

.navbar-brand{
  color:#2563eb !important;
  font-weight:700;
}

.dashboard-card{
  background:white;
  border-radius:18px;
  padding:30px;
  box-shadow:0 15px 35px rgba(0,0,0,.08);
  animation:fadeIn .8s ease;
}

.metric-card{
  background:white;
  border-radius:18px;
  padding:25px;
  text-align:center;
  box-shadow:0 10px 25px rgba(0,0,0,.08);
  transition:.4s;
}

.metric-card:hover{
  transform:translateY(-6px);
}

.action-card{
  background:white;
  border-radius:18px;
  padding:30px;
  text-align:center;
  box-shadow:0 8px 25px rgba(0,0,0,.08);
  transition:.4s;
}

.action-card:hover{
  transform:scale(1.05);
  background:#eef2ff;
}

@keyframes fadeIn{
  from{opacity:0; transform:translateY(20px)}
  to{opacity:1; transform:translateY(0)}
}
</style>
</head>
<body>
