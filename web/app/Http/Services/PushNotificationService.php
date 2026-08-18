<?php

namespace App\Http\Services;

use App\Models\User;


class PushNotificationService
{
    /**
     * S11 — Nom du topic FCM d'un destinataire.
     *
     * Le socle le dérivait de l'ADRESSE E-MAIL (« @ » et « . » remplacés) :
     * connaître l'adresse d'un marchand suffisait donc à s'abonner à ses
     * notifications via `fcmSubscribe`. Le topic est désormais un HMAC de
     * l'identifiant de l'utilisateur avec la clé applicative : il n'est pas
     * devinable depuis une information publique.
     *
     * L'argument reste une adresse e-mail pour ne pas toucher aux ~14 appels
     * existants ; la conversion se fait ici, des deux côtés (abonnement et
     * envoi), ce qui garantit qu'ils coïncident.
     *
     * ⚠️ Changer `APP_KEY` change tous les topics : les appareils déjà abonnés
     * devront se réabonner. Sans conséquence tant que le push est hors service
     * (API FCM legacy arrêtée par Google — voir bloc K de la cartographie).
     */
    public function topicFor($identifier): string
    {
        $base = notificationSettings()->fcm_topic;

        if (blank($identifier)) {
            return $base;
        }

        $userId = null;
        if ($identifier instanceof User) {
            $userId = $identifier->id;
        } elseif (is_numeric($identifier)) {
            $userId = (int) $identifier;
        } else {
            $userId = User::where('email', $identifier)->value('id');
        }

        if (blank($userId)) {
            return $base;
        }

        return $base . '_' . substr(hash_hmac('sha256', 'user:' . $userId, config('app.key')), 0, 32);
    }

    public function sendPushNotification($data, $topicName,$type)
    {

        if (!empty($topicName)) {
            $topic = $this->topicFor($topicName); // S11 : topic non devinable
        } else {
            $topic = notificationSettings()->fcm_topic;
        }

        $final = array(
            'to' => '/topics/' . $topic,
            'priority' => 'high',
            'notification' => [
                'body'  => $data->description,
                'title' => $data->title,
                'sound' => 'Default',
                'image' => $data->image
            ],
        );

        $url = 'https://fcm.googleapis.com/fcm/send';

        $headers = array(
            'Authorization: key=' . notificationSettings()->fcm_secret_key,
            'Content-Type: application/json'
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // S12 : certificat verifie
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($final));
        $result = curl_exec($ch);

        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

    public function sendStatusPushNotification($parcel, $topicName,$msg,$type)
    {
        if (!empty($topicName)) {
            $topic = $this->topicFor($topicName); // S11 : topic non devinable
        } else {
            $topic = notificationSettings()->fcm_topic;
        }

        $final = array(
            'to' => '/topics/' . $topic,
            'priority' => 'high',
            'notification' => [
                "title" => "Your parcel #".$parcel->tracking_id." status updated ".trans("parcelStatus.".$parcel->status),
                "body" => $msg,
                'sound' => 'Default',
            ],
        );
        $url = 'https://fcm.googleapis.com/fcm/send';

        $headers = array(
            'Authorization: key=' . notificationSettings()->fcm_secret_key,
            'Content-Type: application/json'
        );

        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // S12 : certificat verifie
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($final));
        $result = curl_exec($ch);

        curl_close($ch);
        return $result;
    }

    
    

    public function fcmSubscribe($request)
    {

        $deviceToken = $request->device_token;
        // S11 — le topic demande par l'appelant est IGNORE : il permettait de
        // s'abonner aux notifications d'autrui en connaissant son adresse.
        // Le topic est celui de l'utilisateur AUTHENTIFIE, personne d'autre.
        $topic = $this->topicFor(\Illuminate\Support\Facades\Auth::user());


        $headers = array(
            'Authorization: key=' .notificationSettings()->fcm_secret_key,
            'Content-Type: application/json'
        );
        $this->fcmGlobalSubscribe($request);
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://iid.googleapis.com/iid/v1/$deviceToken/rel/topics/$topic");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, array());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            return response()->json([
                'status' => 200,
                'message' => 'Subscribed',
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'status'  => 401,
                'message' => $exception,
            ], 401);
        }
    }

    
    
    public function fcmGlobalSubscribe($request)
    {
        $deviceToken = $request->device_token;
        $topic       = notificationSettings()->fcm_topic;

        $headers = array(
            'Authorization: key=' .notificationSettings()->fcm_secret_key,
            'Content-Type: application/json'
        );

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://iid.googleapis.com/iid/v1/$deviceToken/rel/topics/$topic");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, array());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            return response()->json([
                'status' => 200,
                'message' => 'Global Subscription',
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'status'  => 401,
                'message' => $exception,
            ], 401);
        }
    }

 


    public function fcmUnsubscribe($request)
    {
        $request->validate([
            'device_token' => 'required',
            'topic' => 'nullable',
        ]);

        $deviceToken = $request->token;

        $headers = array(
            'Authorization: key=' .notificationSettings()->fcm_secret_key,
            'Content-Type: application/json'
        );

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://iid.googleapis.com/v1/web/iid/$deviceToken");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);

            return response()->json([
                'status' => 200,
                'message' => 'Unsubscribed',
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'status'  => 401,
                'message' => $exception,
            ], 401);
        }
    } 
    
    public function sendWebNotification($data,$notification,$type,$FcmToken)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';

        if(!blank($FcmToken)) {

            $serverKey = notificationSettings()->fcm_secret_key;
            if ($notification) {
                $pushData = [
                    "registration_ids" => $FcmToken,
                    "notification" => [
                        "title" => "New Parcel #" . $data->id,
                        "body"  => 'A new parcel has been placed ' . $data->merchant->title . ' The parcel amount is ' . $data->cash_collection,
                        'sound' => 'default', // Optional
                        'icon'  => public_path('images/fav.png'),
                    ]
                ];
            } else {
                $pushData = [
                    "registration_ids" => $FcmToken,
                    "notification" => [
                        "title" => $data->title,
                        "body"  => $data->description,
                        'sound' => 'default', // Optional
                        'icon'  => $data->image,
                    ]
                ];
            }

            $encodedData = json_encode($pushData);

            $headers = [
                'Authorization:key=' . $serverKey,
                'Content-Type: application/json',
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // S12 : nom d hote verifie
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // S12 : certificat verifie
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);
            // Execute post
            $result = curl_exec($ch);
            // Close connection
            curl_close($ch);
            // FCM response
            return true;
        }else{
            return true;
        }
    }
}
