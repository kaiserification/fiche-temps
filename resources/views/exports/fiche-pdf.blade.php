<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #000; }
        .container { padding: 15px 20px; }

        .title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            padding: 6px 0 10px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .info-table td {
            padding: 3px 8px;
            font-size: 11px;
            vertical-align: middle;
        }
        .info-table .label { width: 130px; }
        .info-table .value { text-align: center; }
        .info-table .du    { width: 50px; text-align: center; }
        .info-table .date  { text-align: center; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .data-table th {
            background-color: #4BACC6;
            font-weight: bold;
            text-align: center;
            padding: 5px 6px;
            border: 1px solid #000;
            font-size: 10px;
        }
        .data-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: top;
            font-size: 9px;
        }
        .data-table td.center { text-align: center; vertical-align: middle; }
        .task-line { margin-bottom: 2px; }
        .task-line:last-child { margin-bottom: 0; }

        .col-date    { width: 11%; }
        .col-projet  { width: 17%; }
        .col-bu      { width: 17%; }
        .col-tasks   { width: 40%; }
        .col-comment { width: 15%; }
    </style>
</head>
<body>
<div class="container">

    <div class="title">Fiche de temps</div>

    <table class="info-table">
        <tr>
            <td class="label">Prénom(s) &amp; Nom</td>
            <td class="value" colspan="4">{{ $user->name }}</td>
        </tr>
        <tr>
            <td class="label">Matricule</td>
            <td class="value" colspan="4">{{ $user->matricule ?? $fiche->matricule ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Profil</td>
            <td class="value" colspan="4">{{ $user->profil ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Période</td>
            <td class="du">DU</td>
            <td class="date">{{ $fiche->period_start->locale('fr')->isoFormat('D MMMM YYYY') }}</td>
            <td class="du">AU</td>
            <td class="date">{{ $fiche->period_end->locale('fr')->isoFormat('D MMMM YYYY') }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th class="col-date">Date</th>
                <th class="col-projet">Projet</th>
                <th class="col-bu">Business Unit</th>
                <th class="col-tasks">Liste des tâches</th>
                <th class="col-comment">Commentaire</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
                @php
                    $tasks = array_values((array) $entry->tasks);
                @endphp
                <tr>
                    <td class="center">{{ \Carbon\Carbon::parse($entry->day)->format('d/m/Y') }}</td>
                    <td class="center">{{ $fiche->projet }}</td>
                    <td class="center">{{ $fiche->business_unit }}</td>
                    <td>
                        @foreach($tasks as $i => $task)
                            <div class="task-line">{{ ($i + 1) . '. ' . $task }}</div>
                        @endforeach
                    </td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>
</body>
</html>
